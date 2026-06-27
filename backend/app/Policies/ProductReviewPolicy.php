<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Policies\Concerns\HandlesPolicyUsers;

final class ProductReviewPolicy
{
    use HandlesPolicyUsers;

    public function viewAny(mixed $user): bool
    {
        return true;
    }

    public function view(mixed $user, ProductReview $review): bool
    {
        return true;
    }

    public function create(mixed $user): bool
    {
        return $user !== null && ! $this->isAdmin($user);
    }

    public function update(mixed $user, ProductReview $review): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $review->client_id);
    }

    public function delete(mixed $user, ProductReview $review): bool
    {
        return $this->isAdmin($user) || $this->ownsClientId($user, $review->client_id);
    }
}
