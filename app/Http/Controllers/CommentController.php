<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'stranded_incident_id' => 'required|exists:stranded_incidents,id',
        ]);

        Comment::create([
            'text' => $request->text,
            'stranded_incident_id' => $request->stranded_incident_id,
            'user_id' => Auth::id(),
            'is_active' => true,
            'is_automated' => false,
        ]);

        return redirect()->back()->with('success', 'Comment added successfully!');
    }

    public function update(Request $request, Comment $comment)
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $comment->update(['text' => $request->text]);

        return redirect()->back()->with('success', 'Comment updated successfully!');
    }

    public function archive(Comment $comment)
    {
        $comment->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Comment archived successfully!');
    }

}
