<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use App\Events\NewMessageSent;
use App\Models\Chat;
use App\Models\User;

class ChatMessageController extends Controller
{
    public function index(GetMessageRequest $request) : JsonResponse
    {
        $data= $request->validated();
        $chatId = $data['chat_id'];
        $currentPage = $data['page'];
        $pageSize = $data['page_size'] ?? 15;

        $messages = ChatMessage::where('chat_id', $chatId)
            ->with('user')
            ->latest('created_at')
            ->simplePaginate(
                $pageSize,
                ['*'],
                'page',
                $currentPage



            );
        return $this->success(
            $messages -> getCollection()
        );
    }

    public function store(StoreMessageRequest $request) : JsonResponse{
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;

        $chatMessage = ChatMessage::create($data);
        $chatMessage->load('user');

        $this->sendNotificationToOther($chatMessage);

        return $this->success($chatMessage, 'pesan sudah terkirim ');

    }

    private function sendNotificationToOther(ChatMessage $chatMessage) : void{
        $chatId = $chatMessage->chat_id;

        broadcast(new NewMessageSent($chatMessage))->toOthers();

        $user = auth()->user();
        $userId = $user->id;

        $chat = Chat::where('id', $chatMessage->chat_id)
            ->with(['participants' =>  function($query) use ($userId){
                $query->where('user_id', '!=', $userId);
            }])
            ->first();

        if ($chat->participants->count() > 0) {
            $otherUserId = $chat->participants->first()->user_id;

            $otherUser = User::where('id', $otherUserId)->first();
            $otherUser->sendNowMessageNotification([
                'messageData' => [
                    'senderName'=> $user->username,
                    'message'=> $chatMessage->message,
                    'chatId'=> $chatMessage->chat_id
                ]
            ]);

        }
    }
}
