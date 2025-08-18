<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'l33t:seed-subscriptions {--fresh : Run with fresh database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed subscription levels, tokens, counters, and demo users with L33t economy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up L33t subscription system...');

        if ($this->option('fresh')) {
            $this->warn('⚠️ This will refresh your database!');
            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return 0;
            }

            $this->call('migrate:fresh');
        }

        // Run the essential seeders
        $seeders = [
            'SubscriptionLevelSeeder',
            'TokenSeeder',
            'ResettableCounterSeeder',
            'UserSubscriptionSeeder',
            'UserRoleSeeder', // Run last to ensure admin user gets correct role
        ];

        foreach ($seeders as $seeder) {
            $this->info("📊 Running {$seeder}...");
            $this->call('db:seed', ['--class' => $seeder]);
        }

        $this->newLine();
        $this->info('✅ L33t subscription system setup complete!');
        $this->newLine();

        $this->line('🎮 Demo users created:');
        $this->line('   📧 padawan@example.com (password: password) - L33t Padawan');
        $this->line('   📧 jedi@example.com (password: password) - L33t Jedi');
        $this->line('   📧 master@example.com (password: password) - L33t Master');
        $this->line('   📧 free@example.com (password: password) - No Subscription');
        $this->line('   📧 admin@example.com (password: password) - Super Admin');

        $this->newLine();
        $this->line('🔧 Manage subscriptions at: /admin/users');
        $this->line('💰 Manage L33t Bytes at: /admin/token-management');
        $this->line('⚡ Manage L33t Bits at: /admin/bits-management');

        return 0;
    }
}
