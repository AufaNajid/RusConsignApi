<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetChatRequest;
use App\Http\Requests\StoreChatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Chat;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChatController extends Controller
{
    public function index(GetChatRequest $request) : JsonResponse
    {
        $data = $request->validated();

        error_log("AKU INDEX");

        $isPrivate = 1;
        if ($request -> has('is_private')) {
            $isPrivate = (int) $data['is_private'];
        }

        $chats = Chat::where('is_private', $isPrivate)
            ->hasParticipants(auth()->user()->id)
            ->whereHas('messages')
            ->with('lastMessage.user', 'participants.user')
            ->latest('updated_at')
            ->get();


        return $this->success($chats);
    }


    public function store(StoreChatRequest $request) : JsonResponse
    {
        $data = $this->preparedStoreData($request);

        error_log("AKU store");

        if ($data['userId'] == $data['otherUserId']) {
            error_log( $data['userId']);
            error_log( $data['otherUserId'] );
            return $this->error('you cannot chat urself lol');
        }

        $previousChat = $this->getPreviousChat($data['otherUserId']);

        error_log($previousChat);

        if ($previousChat == null) {
            $chat = Chat::create($data['data']);
            $chat->participants()->createMany([
                ['user_id' => $data['userId']],
                ['user_id' => $data['otherUserId']]
            ]);



            $chat->refresh()->load('lastMessage.user', 'participants.user');
            return $this->success($chat);
        }

        return $this->success($previousChat->load('lastMessage.user', 'participants.user'));
    }

    private function getPreviousChat(int $otherUserId) : mixed {
        $userId = auth()->user()->id;

        return Chat::where('is_private', 1)
            ->whereHas('participants', function($query) use ($userId){
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function($query) use ($otherUserId){
                $query->where('user_id', $otherUserId);
            })
            ->first();
    }

    protected function error($message, $statusCode = 400)
    {
        return response()->json(['error' => $message], $statusCode);
    }


    public function preparedStoreData(StoreChatRequest $request) : array
    {
        $data = $request->validated();
        $otherUserId = (int)$data['user_id'];
        unset($data['user_id']);
        $data['created_by'] = auth()->user()->id;


        return[
            'otherUserId'=> $otherUserId,
            'userId'=> auth()->user()->id,
            'data'=> $data
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show($id) : JsonResponse
    {
        error_log("AKU show");

        try {
            $chat = Chat::findOrFail($id);
            $chat->load('lastMessage.user', 'participants.user');
            return $this->success($chat);
        } catch (ModelNotFoundException $e) {
            return $this->error('Chat tidak ditemukan', 404);
        }
    }
}
