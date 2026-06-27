<?php

namespace Tests\Unit;

use App\Enums\Sales\SaleStatus;
use App\Models\AdminUser;
use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Policies\FavoritePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductReviewPolicy;
use App\Policies\SalePolicy;
use App\Policies\UserProfilePolicy;
use Illuminate\Notifications\DatabaseNotification;
use PHPUnit\Framework\TestCase;

final class EnumsAndPoliciesTest extends TestCase
{
    public function test_sale_status_options_are_ui_ready(): void
    {
        $option = SaleStatus::options()[0];

        $this->assertSame('pending', $option['value']);
        $this->assertSame('Pendiente', $option['label']);
        $this->assertArrayHasKey('color', $option);
        $this->assertArrayHasKey('icon', $option);
    }

    public function test_sale_policy_allows_admin_or_owner_only(): void
    {
        $policy = new SalePolicy;
        $sale = new Sale(['client_id' => 10]);

        $owner = new Client(['name' => 'Owner']);
        $owner->user_id = 10;

        $other = new Client(['name' => 'Other']);
        $other->user_id = 11;

        $this->assertTrue($policy->view(new AdminUser, $sale));
        $this->assertTrue($policy->view($owner, $sale));
        $this->assertFalse($policy->view($other, $sale));
    }

    public function test_invoice_policy_allows_admin_or_owner_only(): void
    {
        $policy = new InvoicePolicy;
        $sale = new Sale(['client_id' => 10]);

        $owner = new Client;
        $owner->user_id = 10;
        $other = new Client;
        $other->user_id = 11;

        $this->assertTrue($policy->view(new AdminUser, $sale));
        $this->assertTrue($policy->view($owner, $sale));
        $this->assertFalse($policy->view($other, $sale));
    }

    public function test_favorite_policy_allows_owner_to_view_and_toggle_only_clients(): void
    {
        $policy = new FavoritePolicy;
        $favorite = new FavoriteProduct(['user_id' => 10]);

        $owner = new Client;
        $owner->user_id = 10;
        $other = new Client;
        $other->user_id = 11;

        $this->assertTrue($policy->view(new AdminUser, $favorite));
        $this->assertTrue($policy->view($owner, $favorite));
        $this->assertFalse($policy->view($other, $favorite));
        $this->assertTrue($policy->toggle($owner));
        $this->assertFalse($policy->toggle(new AdminUser));
    }

    public function test_notification_policy_allows_admin_or_owner_only(): void
    {
        $policy = new NotificationPolicy;
        $notification = new DatabaseNotification(['notifiable_id' => 10]);

        $owner = new Client;
        $owner->user_id = 10;
        $other = new Client;
        $other->user_id = 11;

        $this->assertTrue($policy->markRead(new AdminUser, $notification));
        $this->assertTrue($policy->markRead($owner, $notification));
        $this->assertFalse($policy->markRead($other, $notification));
    }

    public function test_review_and_profile_policies_are_owner_scoped(): void
    {
        $reviewPolicy = new ProductReviewPolicy;
        $profilePolicy = new UserProfilePolicy;
        $review = new ProductReview(['client_id' => 10]);
        $profile = new Client;
        $profile->user_id = 10;

        $owner = new Client;
        $owner->user_id = 10;
        $other = new Client;
        $other->user_id = 11;

        $this->assertTrue($reviewPolicy->update($owner, $review));
        $this->assertFalse($reviewPolicy->update($other, $review));
        $this->assertTrue($profilePolicy->update($owner, $profile));
        $this->assertFalse($profilePolicy->update($other, $profile));
    }
}
