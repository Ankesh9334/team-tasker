import express from 'express';
import { createServer as createHttpServer } from 'http';
import { createServer as createHttpsServer } from 'https';
import { Server } from 'socket.io';
import fs from 'fs';

const app = express();
let server;

// Replace these paths with the actual paths to your SSL certificates on your live server
// Example for Let's Encrypt: '/etc/letsencrypt/live/task.coxfuture.com/privkey.pem'
const privateKeyPath = '/path/to/your/private.key';
const certificatePath = '/path/to/your/certificate.crt';

if (fs.existsSync(privateKeyPath) && fs.existsSync(certificatePath)) {
    // Run in HTTPS mode if certificates exist
    const privateKey = fs.readFileSync(privateKeyPath, 'utf8');
    const certificate = fs.readFileSync(certificatePath, 'utf8');
    const credentials = { key: privateKey, cert: certificate };
    
    server = createHttpsServer(credentials, app);
    console.log('Running Socket.IO server in Secure HTTPS mode');
} else {
    // Fallback to HTTP mode (for local XAMPP development)
    server = createHttpServer(app);
    console.log('Running Socket.IO server in HTTP mode (No SSL certificates found)');
}

const io = new Server(server, {
    cors: {
        origin: '*', // Allow all origins for simplicity in dev
        methods: ['GET', 'POST']
    }
});

const onlineUsers = new Map(); // userId -> Set(socketId)
const typingUsers = new Map(); // conversationId -> Set(userId)
const userStatuses = new Map(); // userId -> status string ('online' | 'away' | 'busy' | 'offline')

io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);

    // User status update event
    socket.on('update_status', (data) => {
        if (!data || !data.userId) return;
        const status = data.status || 'online';
        userStatuses.set(Number(data.userId), status);
        io.emit('user_status_changed', { userId: Number(data.userId), status: status });
        io.emit('all_user_statuses', Object.fromEntries(userStatuses));
    });

    // Request initial statuses
    socket.on('get_user_statuses', () => {
        socket.emit('all_user_statuses', Object.fromEntries(userStatuses));
    });

    // Join room event
    socket.on('join_room', (room) => {
        socket.join(room);
        if (!isNaN(room)) {
            socket.join(Number(room));
            socket.join(String(room));
        }
    });

    // Send message event
    socket.on('send_message', (data) => {
        if (!data) return;
        // Broadcast to the specific room (conversation)
        io.to(data.room).emit('receive_message', data);

        // Immediately emit delivery confirmation back to sender
        // (shows double gray ticks as soon as any online user in the room gets it)
        if (data.message && data.message.id) {
            socket.to(data.room).emit('message_delivered', {
                messageId: data.message.id,
                room: data.room
            });
        }
    });

    // Read message event
    socket.on('messages_read', (data) => {
        if (!data) return;
        // Broadcast to the specific room that messages were read
        socket.to(data.room).emit('messages_read_by_user', data);
    });

    // Typing event
    socket.on('typing', (data) => {
        if (!data) return;
        const { room, userId, userName } = data;
        if (!typingUsers.has(room)) {
            typingUsers.set(room, new Set());
        }
        typingUsers.get(room).add(userId);

        socket.to(room).emit('user_typing', { userId, userName, room });
    });

    // Stop typing event
    socket.on('stop_typing', (data) => {
        if (!data) return;
        const { room, userId } = data;
        if (typingUsers.has(room)) {
            typingUsers.get(room).delete(userId);
            if (typingUsers.get(room).size === 0) {
                typingUsers.delete(room);
            }
        }
        socket.to(room).emit('user_stop_typing', { userId, room });
    });

    // User connected with ID
    socket.on('user_connected', (userId) => {
        if (!onlineUsers.has(userId)) {
            onlineUsers.set(userId, new Set());
        }
        onlineUsers.get(userId).add(socket.id);
        io.emit('online_users', Array.from(onlineUsers.keys()));
    });

    // Call & Meeting Signaling Events
    socket.on('call_initiated', (data) => {
        if (!data) return;
        console.log('call_initiated event received:', data);
        // Broadcast incoming call to receiver if online
        if (data.receiverId) {
            const receiverSockets = onlineUsers.get(Number(data.receiverId));
            if (receiverSockets) {
                for (const socketId of receiverSockets) {
                    io.to(socketId).emit('incoming_call', data);
                }
            }
        }
        // Broadcast to room fallback
        if (data.conversationId) {
            socket.to(Number(data.conversationId)).emit('incoming_call', data);
            socket.to(String(data.conversationId)).emit('incoming_call', data);
        } else if (data.room) {
            socket.to(data.room).emit('incoming_call', data);
        }
    });

    socket.on('call_accepted', (data) => {
        if (!data) return;
        if (data.callerId) {
            const callerSockets = onlineUsers.get(Number(data.callerId));
            if (callerSockets) {
                for (const socketId of callerSockets) {
                    io.to(socketId).emit('call_accepted', data);
                }
            }
        }
        if (data.room) {
            socket.to(data.room).emit('call_accepted', data);
        }
    });

    socket.on('call_rejected', (data) => {
        if (!data) return;
        if (data.callerId) {
            const callerSockets = onlineUsers.get(Number(data.callerId));
            if (callerSockets) {
                for (const socketId of callerSockets) {
                    io.to(socketId).emit('call_rejected', data);
                }
            }
        }
        if (data.room) {
            socket.to(data.room).emit('call_rejected', data);
        }
    });

    socket.on('call_cancelled', (data) => {
        if (!data) return;
        if (data.receiverId) {
            const receiverSockets = onlineUsers.get(Number(data.receiverId));
            if (receiverSockets) {
                for (const socketId of receiverSockets) {
                    io.to(socketId).emit('call_cancelled', data);
                }
            }
        }
        if (data.room) {
            socket.to(data.room).emit('call_cancelled', data);
        }
    });

    socket.on('call_ended', (data) => {
        if (!data) return;
        if (data.room) {
            io.to(data.room).emit('call_ended', data);
        }
    });

    socket.on('meeting_invitation', (data) => {
        if (!data) return;
        if (data.userIds && Array.isArray(data.userIds)) {
            data.userIds.forEach(uId => {
                const userSockets = onlineUsers.get(Number(uId));
                if (userSockets) {
                    for (const socketId of userSockets) {
                        io.to(socketId).emit('meeting_invitation', data);
                    }
                }
            });
        }
    });

    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
        // Remove from online users
        for (const [userId, socketIds] of onlineUsers.entries()) {
            if (socketIds.has(socket.id)) {
                socketIds.delete(socket.id);
                if (socketIds.size === 0) {
                    onlineUsers.delete(userId);
                    userStatuses.set(Number(userId), 'offline');
                    io.emit('online_users', Array.from(onlineUsers.keys()));
                    io.emit('user_status_changed', { userId: Number(userId), status: 'offline' });
                    io.emit('all_user_statuses', Object.fromEntries(userStatuses));
                }
                break;
            }
        }
    });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
});
