<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\IdentityReviews;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewAdminIdentity extends Command
{
    protected $signature = 'hazard:review-admin-identity {email}';

    protected $description = 'Designated server operator: approve a pending administrator ID after an independent in-person original-ID check';

    public function handle(IdentityReviews $reviews): int
    {
        if (! filter_var($this->argument('email'), FILTER_VALIDATE_EMAIL)) {
            $this->error('Supply a plain account email.');

            return self::FAILURE;
        }
        $user = User::where('email', strtolower(trim($this->argument('email'))))->where('role', 'admin')->first();
        $id = $user ? DB::table('identity_verifications')->where('user_id', $user->id)->where('status', 'pending')->value('id') : null;
        if (! $id || ! $this->confirm('Are you the designated municipal operator, and have you independently checked this administrator in person against their original Philippine government photo ID and matching account name?')) {
            $this->error('No approval made. A pending administrator submission and independent original-ID check are required.');

            return self::FAILURE;
        }
        try {
            $reviews->decide($id, 'approved', null, null);
        } catch (HttpException $error) {
            $this->error($error->getMessage() ?: 'Approval refused. Check the current account and submission.');

            return self::FAILURE;
        }
        $this->info('Independent operator review recorded; the uploaded image is deleted. Administrator MFA remains required.');

        return self::SUCCESS;
    }
}
