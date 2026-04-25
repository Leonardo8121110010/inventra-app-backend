<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;

class ArticleService
{
    /**
     * Get all active articles (sellable SKUs).
     */
    public function getAll(): Collection
    {
        return Article::where('active', true)->get();
    }

    /**
     * Create a new article.
     */
    public function create(array $data): Article
    {
        $data['active'] = $data['active'] ?? true;
        $data['total_cost'] = ($data['cost'] ?? 0) + ($data['freight'] ?? 0);

        return Article::create($data);
    }

    /**
     * Update an existing article.
     */
    public function update(Article $article, array $data): Article
    {
        // Auto-calculate total_cost if cost or freight changed
        if (array_key_exists('cost', $data) || array_key_exists('freight', $data)) {
            $data['total_cost'] = ($data['cost'] ?? $article->cost) + ($data['freight'] ?? $article->freight);
        }

        $article->update($data);

        return $article;
    }

    /**
     * Soft-deactivate an article.
     */
    public function deactivate(Article $article): Article
    {
        $article->update(['active' => false]);

        return $article;
    }
}
