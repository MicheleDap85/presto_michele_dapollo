<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function homepage(): View
    {
        $articles = collect();

        if (Schema::hasTable('articles')) {
            $articles = Article::query()
                ->accepted()
                ->with(['category', 'images'])
                ->latest()
                ->take(6)
                ->get();
        }

        return view('welcome', compact('articles'));
    }

    public function setLanguage(string $lang): RedirectResponse
    {
        abort_unless(in_array($lang, SetLocaleMiddleware::SUPPORTED_LOCALES, true), 404);

        session()->put('locale', $lang);

        return back();
    }

    public function searchArticles(Request $request): View
    {
        $query = (string) $request->input('query', '');

        try {
            $articles = Article::search($query)
                ->where('is_accepted', true)
                ->query(fn (Builder $builder): Builder => $builder->with(['category', 'images']))
                ->paginate(10);
        } catch (\Throwable $exception) {
            $articles = Article::query()
                ->accepted()
                ->with(['category', 'images'])
                ->where(function (Builder $builder) use ($query): void {
                    $term = '%'.$query.'%';

                    $builder->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('category', function (Builder $category) use ($term): void {
                            $category->where('name', 'like', $term);
                        });
                })
                ->paginate(10);
        }

        return view('article.searched', [
            'articles' => $articles,
            'query' => $query,
        ]);
    }
}
