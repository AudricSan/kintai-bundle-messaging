<?php

declare(strict_types=1);

namespace kintai\Database\Migrations;

use kintai\Contract\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Tables du bundle, créées si absentes (installation neuve). Reprise de la migration du Core 2026_06_09_000011_create_messaging_tables
 * lors de l'extraction (étape 4 du plan du contrat) : sur une instance existante, les tables sont déjà là et ne
 * sont pas touchées ; leurs évolutions futures se font ici.
 */
return new class($this->capsule) extends Migration {
    public function up(): void
    {
        if ($this->schema()->hasTable('message_threads')) {
            return;
        }
        $this->schema()->create('message_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('store_id');
            $table->integer('creator_id');
            $table->string('subject')->nullable();
            $table->string('type')->default('direct');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });

        $this->schema()->create('thread_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id');
            $table->integer('user_id');
            $table->integer('is_read')->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['thread_id', 'user_id']);
            $table->foreign('thread_id')->references('id')->on('message_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        $this->schema()->create('thread_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id');
            $table->integer('sender_id');
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index('thread_id');
            $table->foreign('thread_id')->references('id')->on('message_threads')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Pas de suppression : les tables existaient avant cette migration sur les instances mises à jour, et un retour
        // arrière ne doit pas effacer leurs données.
    }
};
