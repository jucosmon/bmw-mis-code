<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
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

        $user = Auth::user();

        $comment = Comment::create([
            'text' => $request->text,
            'stranded_incident_id' => $request->stranded_incident_id,
            'user_id' => $user?->id,
            'is_active' => true,
        ]);

        $this->createNotification($comment);


        return redirect()->back()->with('success', 'Comment added successfully!');
    }

    protected function createNotification(Comment $comment)
    {
        // Get the authenticated user
        $user = Auth::user();

        if($user){
            switch ($user->user_role) {
                case "bpemo_admin":
                    $userRole = "BPEMO Administrator";
                    break;
                case "bpemo_staff":
                    $userRole = "BPEMO Staff";
                    break;
                case "lgu_responder":
                    $userRole = "LGU Responder";
                    break;
                case "barangay_official":
                    $userRole = "Barangay Official";
                    break;
                default:
                    $userRole = 'Public User';
            }
        }else{
            $userRole = 'Public User';
        }

        $userName = $user ? "{$user->first_name} {$user->last_name}" : 'Anonymous';

        // Create the notification
        Notification::create([
            'content' => "[{$userRole}] {$userName} added a new comment in a stranded incident.",
            'category' => 'general',
            'notif_for' => 'all',
            'type' => 'stranding',
            'is_read' => false,
            'created_at' => now(),
            'stranded_incident_id' => $comment->stranded_incident_id,
            'user_id' => null,
            'comment_id' => $comment->id,
        ]);
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

        if ($comment->notification) {
            $comment->notification()->delete();
        }
        return redirect()->back()->with('success', 'Comment archived successfully!');
    }
}
