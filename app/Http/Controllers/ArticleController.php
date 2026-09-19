<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ArticleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', only: ['create']),
        ];
    }

    public function index(): View
    {
        $articles = Article::query()
            ->accepted()
            ->with(['category', 'images'])
            ->latest()
            ->paginate(6);

        return view('article.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_accepted, 404);

        $article->load(['category', 'user', 'images']);

        return view('article.show', compact('article'));
    }

    public function byCategory(Category $category): View
    {
        $articles = $category->articles()
            ->accepted()
            ->with(['category', 'images'])
            ->latest()
            ->paginate(6);

        return view('article.byCategory', compact('articles', 'category'));
    }

    public function create(): View
    {
        return view('article.create');
    }
}
