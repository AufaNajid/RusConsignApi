        <?php

        use App\Models\User;
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            /**
             * Run the migrations.
             */
            public function up(): void
            {
                Schema::create('mitras', function (Blueprint $table) {
                    $table->id();
                    $table->string("image_profile");
                    $table->string("nama_lengkap");
                    $table->string("nama_toko");
                    $table->integer("nis")->unique();
                    $table->string("no_dompet_digital");
                    $table->string("image_id_card");
                    $table->string("status");
                    $table->integer("pengikut")->default(0);
                    $table->integer("jumlah_jasa")->default(0);
                    $table->integer("jumlah_product")->default(0);
                    $table->float("penilaian")->default(0);
                    $table->string("email")->nullable();
                    $table->string("status_pembayaran")->nullable();
                    $table->string("no_whatsapp");
                    $table->foreignId("user_id")->constrained()->onDelete('cascade');
                    $table->rememberToken();
                    $table->timestamps();
                });
            }

            /**
             * Reverse the migrations.
             */
            public function down(): void
            {
                Schema::dropIfExists('mitras');
            }
        };
