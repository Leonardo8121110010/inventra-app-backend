<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Get all active articles (sellable SKUs).
     */
    public function index(): JsonResponse
    {
        return response()->json($this->articleService->getAll());
    }

    /**
     * Create a new article.
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->articleService->create($request->validated());

        return response()->json($article, 201);
    }

    /**
     * Update an existing article.
     */
    public function update(UpdateArticleRequest $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $updated = $this->articleService->update($article, $request->validated());

        return response()->json($updated);
    }

    /**
     * Soft-deactivate an article.
     */
    public function destroy(string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $this->articleService->deactivate($article);

        return response()->json(['message' => 'Artículo desactivado correctamente']);
    }
}
