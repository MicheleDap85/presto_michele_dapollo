<?php

namespace App\Http\Controllers;

use App\Mail\BecomeRevisor;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RevisorController extends Controller
{
    public function index(): View
    {
        $article_to_check = Article::query()
            ->toBeRevised()
            ->with(['category', 'user', 'images'])
            ->first();

        return view('revisor.index', compact('article_to_check'));
    }

    public function accept(Article $article): RedirectResponse
    {
        $article->setAccepted(true);
        session()->put('last_reviewed_article_id', $article->id);

        return back()->with('message', __('ui.articleAccepted', ['title' => $article->title]));
    }

    public function reject(Article $article): RedirectResponse
    {
        $article->setAccepted(false);
        session()->put('last_reviewed_article_id', $article->id);

        return back()->with('message', __('ui.articleRejected', ['title' => $article->title]));
    }

    public function undoLast(): RedirectResponse
    {
        $articleId = session('last_reviewed_article_id');

        if (! $articleId) {
            return back()->with('errorMessage', __('ui.noUndo'));
        }

        $article = Article::query()->find($articleId);

        if (! $article) {
            session()->forget('last_reviewed_article_id');

            return back()->with('errorMessage', __('ui.noUndo'));
        }

        $article->setAccepted(null);
        session()->forget('last_reviewed_article_id');

        return back()->with('message', __('ui.undoLastMessage', ['title' => $article->title]));
    }

    public function becomeRevisor(): View
    {
        return view('revisor.become');
    }

    public function becomeRevisorSubmit(Request $request): RedirectResponse
    {
        if (Auth::user()->is_revisor) {
            return redirect()
                ->route('revisor.index')
                ->with('message', __('ui.alreadyRevisorFlash'));
        }

        $validated = $request->validate([
            'motivation' => ['nullable', 'string', 'max:1000'],
        ]);

        Mail::to(config('mail.admin_address'))->send(
            new BecomeRevisor(Auth::user(), $validated['motivation'] ?? null)
        );

        return redirect()
            ->route('homepage')
            ->with('message', __('ui.becomeRevisorSuccess'));
    }

    public function makeRevisor(User $user): RedirectResponse
    {
        Artisan::call('app:make-user-revisor', ['email' => $user->email]);

        return redirect()
            ->route('homepage')
            ->with('message', __('ui.userIsNowRevisor', ['name' => $user->name]));
    }
}
