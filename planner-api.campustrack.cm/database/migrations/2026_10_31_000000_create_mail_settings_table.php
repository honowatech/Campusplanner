<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compte d'envoi des emails de la plateforme (réinitialisation du mot de
     * passe…), géré par le super-admin. Le mot de passe est chiffré (cast
     * `encrypted`). Sans ligne active, la configuration du `.env` s'applique.
     */
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mailer')->default('smtp'); // smtp | log
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('encryption')->nullable(); // tls (STARTTLS) | ssl | null
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('from_address')->default('admin@focusrent.cm');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
