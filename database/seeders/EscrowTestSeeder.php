<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Escrow;
use App\Models\JobApplication;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EscrowTestSeeder extends Seeder
{
    /**
     * Create a complete test scenario for the escrow client flow.
     *
     * Test credentials:
     *   Client:  escrowclient@test.com / password
     *   Artisan: escrowartisan@test.com / password
     *
     * This seeder creates 3 jobs at different escrow stages:
     *   1. Job "accepted" with escrow deposit_required  → test deposit funding
     *   2. Job "quoteApproved" with escrow awaiting_remaining_balance → test fund-remaining
     *   3. Job "escrowFunded" with fully_funded escrow → test escrow status view
     */
    public function run(): void
    {
        // ── Users ──
        $client = User::firstOrCreate(
            ['email' => 'escrowclient@test.com'],
            [
                'full_name'         => 'Escrow Test Client',
                'phone'             => '+27810000001',
                'password'          => 'password',
                'role'              => 'client',
                'location'          => 'Johannesburg, South Africa',
                'lat'               => -26.2041,
                'lng'               => 28.0473,
                'account_status'    => 'active',
                'email_verified_at' => now(),
            ]
        );

        $artisan = User::firstOrCreate(
            ['email' => 'escrowartisan@test.com'],
            [
                'full_name'         => 'Escrow Test Artisan',
                'phone'             => '+27810000002',
                'password'          => 'password',
                'role'              => 'artisan',
                'location'          => 'Johannesburg, South Africa',
                'lat'               => -26.2041,
                'lng'               => 28.0473,
                'account_status'    => 'active',
                'email_verified_at' => now(),
            ]
        );

        $category = Category::first();

        $this->command->info("✅  Test client: escrowclient@test.com / password");
        $this->command->info("✅  Test artisan: escrowartisan@test.com / password");

        // ════════════════════════════════════════════════════════
        //  JOB 1 — "accepted" + escrow deposit_required
        //  → Client taps "Fund Escrow Deposit"
        // ════════════════════════════════════════════════════════
        $job1 = ServiceJob::create([
            'client_id'    => $client->id,
            'artisan_id'   => $artisan->id,
            'category_id'  => $category->id,
            'job_type'     => 'custom',
            'description'  => 'Escrow Test – Bathroom plumbing repair. Leaking taps and broken geyser valve.',
            'location'     => 'Sandton, Johannesburg',
            'lat'          => -26.1076,
            'lng'          => 28.0567,
            'status'       => 'accepted',
            'accepted_at'  => now(),
        ]);

        // Application (already accepted)
        JobApplication::create([
            'service_job_id' => $job1->id,
            'artisan_id'     => $artisan->id,
            'status'         => 'accepted',
            'cover_note'     => 'I have 8 years of plumbing experience.',
        ]);

        // Escrow in deposit_required state
        Escrow::create([
            'job_id'         => $job1->id,
            'client_id'      => $client->id,
            'status'         => 'deposit_required',
            'deposit_amount' => 500.00,
        ]);

        $this->command->info("✅  Job #{$job1->id} → status=accepted, escrow=deposit_required (test deposit funding)");

        // ════════════════════════════════════════════════════════
        //  JOB 2 — "quoteApproved" + escrow awaiting_remaining_balance
        //  → Client taps "Fund Remaining Balance"
        // ════════════════════════════════════════════════════════
        $job2 = ServiceJob::create([
            'client_id'    => $client->id,
            'artisan_id'   => $artisan->id,
            'category_id'  => $category->id,
            'job_type'     => 'custom',
            'description'  => 'Escrow Test – Full kitchen electrical rewiring and new DB board installation.',
            'location'     => 'Rosebank, Johannesburg',
            'lat'          => -26.1453,
            'lng'          => 28.0396,
            'status'       => 'quoteApproved',
            'accepted_at'  => now()->subDays(3),
        ]);

        JobApplication::create([
            'service_job_id' => $job2->id,
            'artisan_id'     => $artisan->id,
            'status'         => 'accepted',
            'cover_note'     => 'Licensed electrician with COC certification.',
        ]);

        // Quote
        $quote2 = Quote::create([
            'service_job_id'     => $job2->id,
            'artisan_id'         => $artisan->id,
            'status'             => 'approved',
            'total_amount'       => 4500.00,
            'labor_total'        => 3000.00,
            'material_total'     => 1500.00,
            'timeline'           => '3-4 working days',
            'notes'              => 'Includes new DB board, cabling, and circuit breakers.',
            'submitted_at'       => now()->subDays(1),
            'client_responded_at'=> now(),
        ]);

        QuoteItem::insert([
            [
                'quote_id'    => $quote2->id,
                'type'        => 'labor',
                'description' => 'Electrical rewiring (per room)',
                'quantity'    => 3,
                'unit_price'  => 800.00,
                'total_price' => 2400.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'quote_id'    => $quote2->id,
                'type'        => 'labor',
                'description' => 'DB board installation',
                'quantity'    => 1,
                'unit_price'  => 600.00,
                'total_price' => 600.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'quote_id'    => $quote2->id,
                'type'        => 'material',
                'description' => 'DB board + circuit breakers',
                'quantity'    => 1,
                'unit_price'  => 950.00,
                'total_price' => 950.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'quote_id'    => $quote2->id,
                'type'        => 'material',
                'description' => 'Electrical cabling (50m roll)',
                'quantity'    => 1,
                'unit_price'  => 550.00,
                'total_price' => 550.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // Escrow — deposit already funded, remaining due
        $escrow2 = Escrow::create([
            'job_id'           => $job2->id,
            'client_id'        => $client->id,
            'status'           => 'awaiting_remaining_balance',
            'deposit_amount'   => 500.00,
            'remaining_amount' => 4000.00,   // 4500 total - 500 deposit
            'material_amount'  => 1500.00,
            'total_funded'     => 500.00,
        ]);

        // Record the deposit transaction that already happened
        $escrow2->transactions()->create([
            'job_id'            => $job2->id,
            'type'              => 'deposit',
            'amount'            => 500.00,
            'payment_method'    => 'card',
            'payment_reference' => 'TEST_DEP_' . strtoupper(uniqid()),
            'description'       => 'Initial escrow deposit',
        ]);

        $this->command->info("✅  Job #{$job2->id} → status=quoteApproved, escrow=awaiting_remaining_balance (test fund-remaining)");

        // ════════════════════════════════════════════════════════
        //  JOB 3 — "escrowFunded" + fully_funded escrow
        //  → Client taps "View Escrow Status"
        // ════════════════════════════════════════════════════════
        $job3 = ServiceJob::create([
            'client_id'    => $client->id,
            'artisan_id'   => $artisan->id,
            'category_id'  => $category->id,
            'job_type'     => 'custom',
            'description'  => 'Escrow Test – Ceiling repair and repaint after water damage in bedroom.',
            'location'     => 'Bryanston, Johannesburg',
            'lat'          => -26.0599,
            'lng'          => 28.0106,
            'status'       => 'escrowFunded',
            'accepted_at'  => now()->subDays(7),
            'started_at'   => now()->subDays(2),
        ]);

        JobApplication::create([
            'service_job_id' => $job3->id,
            'artisan_id'     => $artisan->id,
            'status'         => 'accepted',
            'cover_note'     => 'Experienced ceiling & painting specialist.',
        ]);

        $quote3 = Quote::create([
            'service_job_id'     => $job3->id,
            'artisan_id'         => $artisan->id,
            'status'             => 'approved',
            'total_amount'       => 3200.00,
            'labor_total'        => 2400.00,
            'material_total'     => 800.00,
            'timeline'           => '2 working days',
            'notes'              => 'Patch ceiling, prime and repaint.',
            'submitted_at'       => now()->subDays(5),
            'client_responded_at'=> now()->subDays(4),
        ]);

        QuoteItem::insert([
            [
                'quote_id'    => $quote3->id,
                'type'        => 'labor',
                'description' => 'Ceiling patching & prep',
                'quantity'    => 1,
                'unit_price'  => 1200.00,
                'total_price' => 1200.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'quote_id'    => $quote3->id,
                'type'        => 'labor',
                'description' => 'Priming & painting (2 coats)',
                'quantity'    => 1,
                'unit_price'  => 1200.00,
                'total_price' => 1200.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'quote_id'    => $quote3->id,
                'type'        => 'material',
                'description' => 'Ceiling board, filler, paint',
                'quantity'    => 1,
                'unit_price'  => 800.00,
                'total_price' => 800.00,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // Escrow fully funded
        $escrow3 = Escrow::create([
            'job_id'           => $job3->id,
            'client_id'        => $client->id,
            'status'           => 'fully_funded',
            'deposit_amount'   => 500.00,
            'remaining_amount' => 2700.00,
            'material_amount'  => 800.00,
            'total_funded'     => 3200.00,
            'fully_funded_at'  => now()->subDays(3),
        ]);

        // Deposit transaction
        $escrow3->transactions()->create([
            'job_id'            => $job3->id,
            'type'              => 'deposit',
            'amount'            => 500.00,
            'payment_method'    => 'card',
            'payment_reference' => 'TEST_DEP_' . strtoupper(uniqid()),
            'description'       => 'Initial escrow deposit',
            'created_at'        => now()->subDays(6),
            'updated_at'        => now()->subDays(6),
        ]);

        // Remaining balance transaction
        $escrow3->transactions()->create([
            'job_id'            => $job3->id,
            'type'              => 'remaining_balance',
            'amount'            => 2700.00,
            'payment_method'    => 'bank_transfer',
            'payment_reference' => 'TEST_REM_' . strtoupper(uniqid()),
            'description'       => 'Remaining balance payment',
            'created_at'        => now()->subDays(3),
            'updated_at'        => now()->subDays(3),
        ]);

        $this->command->info("✅  Job #{$job3->id} → status=escrowFunded, escrow=fully_funded (test status view)");

        $this->command->info('');
        $this->command->info('🧪  Escrow test data seeded successfully!');
        $this->command->info("   Login as: escrowclient@test.com / password");
        $this->command->info("   3 jobs ready for testing different escrow stages.");
    }
}
