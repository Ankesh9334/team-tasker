<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Status;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class SettingsController extends Controller
{
    public function general()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return view('admin.settings.general', compact('settings'));
    }

    public function autostop()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return view('admin.settings.autostop', compact('settings'));
    }

    public function storeAutostop(Request $request)
    {
        $request->validate([
            'auto_stop_timers' => 'required|in:yes,no',
            'office_close_time' => 'required_if:auto_stop_timers,yes',
        ]);

        Setting::updateOrCreate(['key' => 'auto_stop_timers'], ['value' => $request->auto_stop_timers]);
        if ($request->has('office_close_time')) {
            Setting::updateOrCreate(['key' => 'office_close_time'], ['value' => $request->office_close_time]);
        }

        return back()->with('success', 'Auto Stop Timer settings updated successfully.');
    }

    public function email()
    {
        // Fetch all settings
        $settings = Setting::all()->pluck('value', 'key');

        return view('admin.settings.email', compact('settings'));
    }

    public function statuses(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);

        $statuses = Status::withCount('tasks')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            })
            ->orderBy('order')
            ->paginate($perPage > 0 ? $perPage : 15)
            ->withQueryString();

        $totalStatuses = Status::count();
        $defaultStatus = Status::where('is_default', true)->first();
        $inUseStatuses = Status::has('tasks')->count();
        $totalTasksInStatuses = Task::count();

        $statusStats = [
            'total' => $totalStatuses,
            'default_name' => $defaultStatus ? $defaultStatus->name : 'None',
            'in_use' => $inUseStatuses,
            'total_tasks' => $totalTasksInStatuses,
        ];

        return view('admin.settings.statuses', compact('statuses', 'statusStats'));
    }

    public function storeGeneral(Request $request)
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        if ($request->filled('app_name')) {
            Setting::updateOrCreate(
                ['key' => 'app_name'],
                ['value' => $request->app_name]
            );
        }

        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $path]
            );
        }

        $this->updateManifestFile();

        return back()->with('success', 'General settings updated successfully.');
    }

    private function updateManifestFile()
    {
        try {
            $settings = Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
            $appName = $settings['app_name'] ?? 'TeamTasker';
            $appLogo = $settings['app_logo'] ?? null;

            $pwa192Path = public_path('icons/pwa-192x192.png');
            $pwa512Path = public_path('icons/pwa-512x512.png');

            $hasCustomPwaIcon = false;

            if ($appLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($appLogo)) {
                $fullLogoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($appLogo);
                $hasCustomPwaIcon = $this->generatePwaIconsFromImage($fullLogoPath, $pwa192Path, $pwa512Path);
            }

            $icon192Src = $hasCustomPwaIcon ? '/icons/pwa-192x192.png' : '/icons/icon-192x192.png';
            $icon512Src = $hasCustomPwaIcon ? '/icons/pwa-512x512.png' : '/icons/icon-512x512.png';

            $manifestData = [
                'name' => $appName,
                'short_name' => $appName,
                'description' => 'WhatsApp-style Chat, Audio/Video Meetings & Task Management Platform',
                'start_url' => '/admin/chat',
                'scope' => '/',
                'display' => 'standalone',
                'background_color' => '#0b141a',
                'theme_color' => '#00a884',
                'orientation' => 'any',
                'icons' => [
                    [
                        'src' => $icon192Src,
                        'sizes' => '192x192',
                        'type' => 'image/png',
                        'purpose' => 'any maskable',
                    ],
                    [
                        'src' => $icon512Src,
                        'sizes' => '512x512',
                        'type' => 'image/png',
                        'purpose' => 'any maskable',
                    ],
                ],
            ];

            file_put_contents(public_path('manifest.json'), json_encode($manifestData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update manifest.json: '.$e->getMessage());
        }
    }

    private function generatePwaIconsFromImage($sourcePath, $target192, $target512)
    {
        try {
            $info = @getimagesize($sourcePath);
            if (! $info) {
                return false;
            }

            $mime = $info['mime'];
            switch ($mime) {
                case 'image/jpeg':
                    $srcImg = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $srcImg = @imagecreatefrompng($sourcePath);
                    break;
                case 'image/webp':
                    $srcImg = @imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return false;
            }

            if (! $srcImg) {
                return false;
            }

            $origW = imagesx($srcImg);
            $origH = imagesy($srcImg);

            // Create 192x192 & 512x512 square icons with dark background (#0b141a) or transparency
            $sizes = [192 => $target192, 512 => $target512];

            if (! file_exists(dirname($target192))) {
                mkdir(dirname($target192), 0755, true);
            }

            foreach ($sizes as $size => $outPath) {
                $dstImg = imagecreatetruecolor($size, $size);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);

                if ($mime === 'image/png' || $mime === 'image/webp') {
                    // Fully transparent background for PNG/WebP icons
                    $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
                    imagefilledrectangle($dstImg, 0, 0, $size, $size, $transparent);
                    imagealphablending($dstImg, true);
                } else {
                    // Solid dark background for JPEG/other non-transparent images
                    $bgColor = imagecolorallocatealpha($dstImg, 11, 20, 26, 0);
                    imagefilledrectangle($dstImg, 0, 0, $size, $size, $bgColor);
                }

                // Calculate aspect ratio fit (100% full fit)
                $ratio = min($size / $origW, $size / $origH);
                $newW = (int) ($origW * $ratio);
                $newH = (int) ($origH * $ratio);
                $dstX = (int) (($size - $newW) / 2);
                $dstY = (int) (($size - $newH) / 2);

                imagecopyresampled($dstImg, $srcImg, $dstX, $dstY, 0, 0, $newW, $newH, $origW, $origH);
                imagepng($dstImg, $outPath, 6);
                imagedestroy($dstImg);
            }

            imagedestroy($srcImg);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating PWA icons: '.$e->getMessage());

            return false;
        }
    }

    public function storeEmail(Request $request)
    {
        $data = $request->validate([
            'imap_host' => 'nullable|string',
            'imap_port' => 'nullable|integer',
            'imap_user' => 'nullable|string',
            'imap_password' => 'nullable|string',
            'imap_encryption' => 'nullable|string|in:ssl,tls,null',

            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_user' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|in:ssl,tls,null',
            'from_email' => 'nullable|email',
            'from_name' => 'nullable|string',
        ]);

        // Validate SMTP connectivity if host is provided
        if (! empty($data['smtp_host'])) {
            try {
                $this->testSmtpConnection($data);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['smtp_host' => 'SMTP Connection failed: '.$e->getMessage()]);
            }
        }

        // Validate IMAP connectivity
        if (! empty($data['imap_host'])) {
            try {
                $this->testImapConnection($data);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['imap_host' => 'IMAP Connection failed: '.$e->getMessage()]);
            }
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Email settings validated and updated successfully.');
    }

    private function testSmtpConnection($config)
    {
        $encryption = $config['smtp_encryption'] ?? 'null';
        $scheme = 'smtp';

        if ($encryption === 'ssl') {
            $scheme = 'smtps';
        }

        $dsn = sprintf(
            '%s://%s:%s@%s:%s',
            $scheme,
            urlencode($config['smtp_user'] ?? ''),
            urlencode($config['smtp_password'] ?? ''),
            $config['smtp_host'],
            $config['smtp_port'] ?? 587
        );

        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $email = (new Email)
            ->from($config['from_email'] ?? 'test@example.com')
            ->to('test@example.com')
            ->subject('SMTP Connection Test')
            ->text('Checking connection...');

        // We don't actually send, just verify the transport can connect
        // Symfony Mailer doesn't have a direct 'connect' method in TransportInterface
        // But attempt to send a dummy message will trigger connection
        // Alternatively, use EsmtpTransport specifically if available to call start()
        if (method_exists($transport, 'start')) {
            $transport->start();
            $transport->stop();
        } else {
            // For general transports, we might need a dummy send or better verification
            // Since Esmtp is almost certainly used for 'smtp://' DSN:
            try {
                $transport->send($email);
            } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                // If it's just a transport error (like connection refused), throw it
                throw $e;
            } catch (\Exception $e) {
                // Ignore errors related to local sending if connection worked
                if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'could not connect')) {
                    throw $e;
                }
            }
        }
    }

    private function testImapConnection($config)
    {
        $encryption = $config['imap_encryption'] ?? 'null';

        $client = \Webklex\IMAP\Facades\Client::make([
            'host' => $config['imap_host'],
            'port' => $config['imap_port'],
            'encryption' => $encryption === 'null' ? false : $encryption,
            'validate_cert' => false, // Set to false for easier initial setup, or true for production
            'username' => $config['imap_user'],
            'password' => $config['imap_password'],
            'protocol' => 'imap',
        ]);

        $client->connect();
        $client->disconnect();
    }

    // Status Management
    public function storeStatus(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'slug' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_default' => 'nullable|boolean',
        ]);

        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Status::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Status::where('is_default', true)->update(['is_default' => false]);
        }

        $maxOrder = Status::max('order') ?? 0;
        $order = $request->filled('order') ? (int) $request->order : ($maxOrder + 1);

        Status::create([
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
            'order' => $order,
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Status created successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'slug' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_default' => 'nullable|boolean',
        ]);

        $status = Status::findOrFail($id);
        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name);

        // Ensure unique slug excluding this status
        $baseSlug = $slug;
        $counter = 1;
        while (Status::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Status::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        $status->update([
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
            'order' => $request->filled('order') ? (int) $request->order : $status->order,
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    public function destroyStatus($id)
    {
        $status = Status::withCount('tasks')->findOrFail($id);

        if ($status->tasks_count > 0) {
            return back()->with('error', "Cannot delete status \"{$status->name}\" because it has {$status->tasks_count} associated task(s).");
        }

        if ($status->is_default) {
            return back()->with('error', "Cannot delete default status \"{$status->name}\".");
        }

        $status->delete();

        return back()->with('success', 'Status deleted successfully.');
    }

    public function bulkStatusAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return back()->with('error', 'Please select at least one status.');
        }

        if ($action === 'delete') {
            $statuses = Status::withCount('tasks')->whereIn('id', $ids)->get();
            $deleted = 0;
            $skipped = 0;

            foreach ($statuses as $status) {
                if ($status->is_default || $status->tasks_count > 0) {
                    $skipped++;

                    continue;
                }
                $status->delete();
                $deleted++;
            }

            if ($deleted > 0 && $skipped > 0) {
                return back()->with('success', "{$deleted} status(es) deleted. {$skipped} status(es) were skipped because they are default or have associated tasks.");
            } elseif ($deleted > 0) {
                return back()->with('success', "{$deleted} status(es) deleted successfully.");
            } else {
                return back()->with('error', 'None of the selected statuses could be deleted because they are default statuses or have active tasks.');
            }
        }

        return back()->with('error', 'Invalid bulk action.');
    }

    // Tag Management
    public function tags(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);

        $tags = Tag::withCount('tasks')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage > 0 ? $perPage : 15)
            ->withQueryString();

        $totalTags = Tag::count();
        $usedTags = Tag::has('tasks')->count();
        $totalTaggedTasks = \DB::table('tag_task')->count();

        $tagStats = [
            'total' => $totalTags,
            'used' => $usedTags,
            'unused' => max(0, $totalTags - $usedTags),
            'tasks_count' => $totalTaggedTasks,
        ];

        return view('admin.settings.tags', compact('tags', 'tagStats'));
    }

    public function storeTag(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'slug' => 'nullable|string|max:255',
        ]);

        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Tag::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Tag::create([
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
        ]);

        return back()->with('success', 'Tag created successfully.');
    }

    public function updateTag(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'slug' => 'nullable|string|max:255',
        ]);

        $tag = Tag::findOrFail($id);
        $slug = $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name);

        // Ensure unique slug excluding this tag
        $baseSlug = $slug;
        $counter = 1;
        while (Tag::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $tag->update([
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
        ]);

        return back()->with('success', 'Tag updated successfully.');
    }

    public function destroyTag($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->tasks()->detach();
        $tag->delete();

        return back()->with('success', 'Tag deleted successfully.');
    }

    public function bulkTagAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return back()->with('error', 'Please select at least one tag.');
        }

        if ($action === 'delete') {
            $tags = Tag::whereIn('id', $ids)->get();
            $count = $tags->count();
            foreach ($tags as $tag) {
                $tag->tasks()->detach();
                $tag->delete();
            }

            return back()->with('success', "{$count} tag(s) deleted successfully.");
        }

        return back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Centralized HR Settings Hub
     */
    public function hrSettings()
    {
        $officeStartTime = Setting::where('key', 'office_start_time')->value('value') ?? '09:15';
        $officeEndTime = Setting::where('key', 'office_end_time')->value('value') ?? '18:00';
        $workingDays = Setting::where('key', 'working_days')->value('value') ?? '5';
        $allowedIps = Setting::where('key', 'allowed_ips')->value('value') ?? '';
        $autoStopTimers = Setting::where('key', 'auto_stop_timers')->value('value') ?? 'no';
        $officeCloseTime = Setting::where('key', 'office_close_time')->value('value') ?? '18:00';

        $totalUsers = \App\Models\User::count();
        $totalRoles = \App\Models\Role::count();
        $pendingApprovals = \App\Models\Attendance::where('approval_status', 'pending')->count();

        return view('admin.settings.hr', compact(
            'officeStartTime',
            'officeEndTime',
            'workingDays',
            'allowedIps',
            'autoStopTimers',
            'officeCloseTime',
            'totalUsers',
            'totalRoles',
            'pendingApprovals'
        ));
    }
}
