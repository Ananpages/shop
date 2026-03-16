<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    // GET /api/chat/conversations
    public function conversations(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $isSeller = in_array($request->user()->role, ['seller', 'admin']);

        $query = Conversation::with([
            'buyer:id,name,avatar',
            'shop:id,name,logo,slug,phone',
        ])->orderByDesc('last_message_at');

        // ✅ FIXED: show ALL conversations where user is buyer OR seller
        // A seller can also be a buyer (chatting with other shops)
        $shop = Shop::where('user_id', $userId)->first();
        $query->where(function ($q) use ($userId, $shop) {
            $q->where('buyer_id', $userId)
              ->orWhere('seller_id', $userId);
            if ($shop) {
                $q->orWhere('shop_id', $shop->id);
            }
        });

        $conversations = $query->get()->map(function ($conv) {
            return [
                'id'              => $conv->id,
                'shop_id'         => $conv->shop_id,
                'shop_name'       => $conv->shop->name ?? '',
                'shop_logo'       => $conv->shop->logo ?? '',
                'shop_phone'      => $conv->shop->phone ?? '',
                'buyer_id'        => $conv->buyer_id,
                'buyer_name'      => $conv->buyer->name ?? '',
                'buyer_avatar'    => $conv->buyer->avatar ?? '',
                'seller_id'       => $conv->seller_id,
                'last_message'    => $conv->last_message,
                'last_message_at' => $conv->last_message_at,
                'buyer_unread'    => $conv->buyer_unread ?? 0,
                'seller_unread'   => $conv->seller_unread ?? 0,
            ];
        });

        return Response::json(['success' => true, 'data' => $conversations]);
    }

    // POST /api/chat/start
    public function start(Request $request): JsonResponse
    {
        $request->validate(['shop_id' => 'required|exists:shops,id']);
        $shop = Shop::findOrFail($request->shop_id);

        if ($shop->user_id === $request->user()->id) {
            return Response::json(['success' => false, 'message' => 'Cannot chat with your own shop'], 400);
        }

        $conversation = Conversation::firstOrCreate(
            ['buyer_id' => $request->user()->id, 'shop_id' => $shop->id],
            ['id' => Str::uuid(), 'seller_id' => $shop->user_id, 'shop_id' => $shop->id]
        );

        // Backfill seller_id if missing on old conversations
        if (!$conversation->seller_id) {
            $conversation->update(['seller_id' => $shop->user_id]);
        }

        return Response::json(['success' => true, 'data' => ['id' => $conversation->id]]);
    }

    // GET /api/chat/{id}/messages
    public function messages(Request $request, string $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $this->authorizeConversation($conversation, $request->user());
        $conversation->markReadFor($request->user());

        $messages = Message::where('conversation_id', $id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'content'     => $m->content,
                'type'        => $m->type ?? 'text',
                'meta'        => $m->meta ? json_decode($m->meta, true) : null,
                'sender_id'   => $m->sender_id,
                'sender_name' => $m->sender->name ?? '',
                'is_read'     => $m->is_read ?? false,
                'created_at'  => $m->created_at,
            ]);

        return Response::json(['success' => true, 'data' => $messages]);
    }

    // POST /api/chat/{id}/messages
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'type'    => 'sometimes|in:text,product,image',
            'meta'    => 'sometimes|nullable|string',
        ]);

        $conversation = Conversation::findOrFail($id);
        $this->authorizeConversation($conversation, $request->user());

        $message = Message::create([
            'id'              => Str::uuid(),
            'conversation_id' => $id,
            'sender_id'       => $request->user()->id,
            'content'         => $request->input('content'),
            'type'            => $request->get('type', 'text'),
            'meta'            => $request->input('meta'), // ✅ product inquiry metadata
            'is_read'         => false,
        ]);

        $conversation->update([
            'last_message'    => $request->input('content'),
            'last_message_at' => now(),
        ]);

        $conversation->incrementUnreadFor($request->user()->id);

        return Response::json([
            'success' => true,
            'data'    => [
                'id'          => $message->id,
                'content'     => $message->content,
                'type'        => $message->type,
                'meta'        => $message->meta ? json_decode($message->meta, true) : null,
                'sender_id'   => $message->sender_id,
                'sender_name' => $request->user()->name,
                'created_at'  => $message->created_at,
            ],
        ], 201);
    }

    // GET /api/chat/unread/count
    public function unreadCount(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $isSeller = in_array($request->user()->role, ['seller', 'admin']);
        $shop     = $isSeller ? Shop::where('user_id', $userId)->first() : null;

        if ($isSeller && $shop) {
            $count = Conversation::where(function ($q) use ($userId, $shop) {
                $q->where('seller_id', $userId)->orWhere('shop_id', $shop->id);
            })->sum('seller_unread');
        } else {
            $count = Conversation::where('buyer_id', $userId)->sum('buyer_unread');
        }

        return Response::json(['success' => true, 'data' => ['count' => $count]]);
    }

    private function authorizeConversation(Conversation $conv, $user): void
    {
        if ($conv->buyer_id !== $user->id && $conv->seller_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Forbidden');
        }
    }
}
