<?php

namespace App\Http\Controllers;

use App\Chat;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function index($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return $this->sendResponse('error', 'User Tidak Ada', null, 404);
        }
        
        $from = User::select('users.id', 'users.name', 'users.email', DB::raw('COUNT(is_read) as unread'))->leftJoin('chats', 'users.id' , '=', 'chats.from')->where('chats.to', $user_id)->where('users.id', '!=', $user_id)->groupBy('users.id', 'users.name', 'users.email')->with(['userdetail' => function($query) {
            $query->select('user_id', 'avatar'); 
        }])->get()->toArray();

        $to = User::select('users.id', 'users.name', 'users.email', DB::raw('COUNT(is_read) as unread'))->leftJoin('chats', 'users.id' , '=', 'chats.to')->where('chats.from', $user_id)->where('users.id', '!=', $user_id)->groupBy('users.id', 'users.name', 'users.email')->with(['userdetail' => function($query) {
            $query->select('user_id', 'avatar'); 
        }])->get()->toArray();

        $chats = array_unique(array_merge($from, $to), SORT_REGULAR);
        // $chats = Chat::where('from', $user_id)->orWhere('to', $user_id)->get();

        if (!$chats) {
            return $this->sendResponse('error', 'Chat Kosong', null, 404);
        }

        return $this->sendResponse('success', 'Chat Berhasil Diambil', $chats, 200);
    }

    public function getMessage(Request $request, $user_id)
    {
        $from = $request->from;

        if ($user_id == $from) {
            return $this->sendResponse('error', 'User sama', null, 404);
        }

        // when click readed
        Chat::where(['from' => $from, 'to' => $user_id])->update(['is_read' => 1]);
        
        $chats = Chat::where(function ($query) use ($user_id, $from) {
            $query->where('from', $from)->where('to', $user_id);
        })->orWhere(function ($query) use ($user_id, $from) {
            $query->where('from', $user_id)->where('to', $from);
        })->get();
        
        if ($chats->isEmpty()) {
            return $this->sendResponse('error', 'Chat Kosong', null, 404);
        }

        return $this->sendResponse('success', 'Chat Berhasil Diambil', $chats, 200);
    }

    public function sendMessage(Request $request, $user_id)
    {
        if ($user_id == $request->to) {
            return $this->sendResponse('error', 'User sama', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'to' => 'required|integer',
            'chat' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response($validator->errors());
        }

        $chat = new Chat;
        $chat->from = $user_id;
        $chat->to = $request->to;
        $chat->chat = $request->chat;

        try {
            $chat->save();

            return $this->sendResponse('success', 'Chat Dikirim', $chat, 200);
        } catch (\Throwable $th) {
            return $this->sendResponse('error', 'Chat Gagal Dikirim', $th->getMessage(), 500);
        }
    }
}
