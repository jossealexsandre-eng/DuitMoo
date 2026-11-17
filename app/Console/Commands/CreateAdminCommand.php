<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserInitializationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duitmoo:create-admin 
                            {--name= : Nama lengkap admin} 
                            {--email= : Alamat email admin} 
                            {--password= : Password akun admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun administrator DuitMoo secara aman di database lokal';

    /**
     * Execute the console command.
     */
    public function handle(UserInitializationService $initService): int
    {
        $this->info('=== DuitMoo: Buat Akun Administrator ===');

        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Admin', 'Admin DuitMoo');
        $email = $this->option('email') ?: $this->ask('Masukkan Email Admin', 'admin@duitmoo.local');
        $password = $this->option('password') ?: $this->secret('Masukkan Password Admin');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->info("Akun pengguna yang sudah ada ($email) berhasil ditingkatkan menjadi Admin.");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->info("Akun Admin baru ($email) berhasil dibuat!");
        }

        $initService->initialize($user);

        return 0;
    }
}
