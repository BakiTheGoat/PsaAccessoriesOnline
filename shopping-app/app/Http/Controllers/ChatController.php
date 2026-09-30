<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Show (or start) a support chat about one product. A thread is
     * identified by (product, buyer) — since this is a single shop, any
     * staff/admin account can view and reply to any buyer's thread, so
     * there's no seller_id to match against anymore.
     *
     * - A buyer viewing/starting a chat is always themself.
     * - Staff/admin must pass ?buyer=ID to say which buyer's thread
     *   they're opening (their dashboard's inbox links already do this).
     */
    public function show(Request $request, Product $product)
    {
        $buyer = $this->resolveBuyer($request);

        $messages = $this->threadQuery($product, $buyer)->get();

        return view('chat.show', compact('product', 'buyer', 'messages'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $buyer = $this->resolveBuyer($request);

        Chat::create([
            'buyer_id' => $buyer->id,
            'product_id' => $product->id,
            'sender_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return redirect()->route('chat.show', array_filter([
            'product' => $product->id,
            'buyer' => $request->user()->isBackOffice() ? $buyer->id : null,
        ]));
    }

    /** JSON endpoint the chat page polls every few seconds for new messages. */
    public function messages(Request $request, Product $product)
    {
        $buyer = $this->resolveBuyer($request);
        $currentUserId = $request->user()->id;

        $payload = $this->threadQuery($product, $buyer)
            ->get()
            ->map(fn (Chat $chat) => [
                'id' => $chat->id,
                'message' => $chat->message,
                'sender_name' => $chat->sender->name,
                'is_mine' => $chat->sender_id === $currentUserId,
                'created_at' => $chat->created_at->format('M j, g:i A'),
            ]);

        return response()->json($payload);
    }

    private function threadQuery(Product $product, User $buyer)
    {
        return Chat::with(['buyer', 'sender'])
            ->where('product_id', $product->id)
            ->where('buyer_id', $buyer->id)
            ->orderBy('created_at');
    }

    /**
     * A regular buyer is always the buyer for their own thread. A
     * staff/admin account viewing the shared inbox must specify which
     * buyer's conversation they're opening via ?buyer=ID.
     */
    private function resolveBuyer(Request $request): User
    {
        $user = $request->user();

        if ($user->isBackOffice()) {
            $buyerId = $request->query('buyer');

            abort_if(! $buyerId, 400, 'A buyer must be specified when staff opens a chat thread.');

            return User::findOrFail($buyerId);
        }

        return $user;
    }
}
