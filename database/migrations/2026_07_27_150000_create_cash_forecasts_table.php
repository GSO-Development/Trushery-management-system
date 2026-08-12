<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 13-Week Rolling Cash Flow Forecast
     * Matches Excel sheet "2. GSS" (and similar per entity)
     *
     * One row per company per week (W1..W13).
     * Covers: Opening Cash, Operating Inflows, Operating Outflows,
     *         Net Operating, Capex, Debt Service, Other, Net Cash Flow, Closing Cash
     */
    public function up(): void
    {
        Schema::create('cash_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->date('forecast_from')->comment('Start date of this 13-week forecast period');
            $table->unsignedTinyInteger('week_number')->comment('1 to 13');

            $table->decimal('opening_cash', 18, 2)->default(0)->comment('Opening cash balance for the week');
            $table->decimal('operating_inflows', 18, 2)->default(0)->comment('Customer collections + Loan drawdowns');
            $table->decimal('operating_outflows', 18, 2)->default(0)->comment('Supplier payments + salaries + taxes');
            $table->decimal('capex', 18, 2)->default(0)->comment('Capital expenditure for the week');
            $table->decimal('debt_service', 18, 2)->default(0)->comment('Loan repayments + interest payments');
            $table->decimal('other', 18, 2)->default(0)->comment('Other cash flows');

            // Computed columns (calculated in PHP for display)
            // net_operating = operating_inflows - operating_outflows
            // net_cash_flow = net_operating - capex - debt_service + other
            // closing_cash  = opening_cash + net_cash_flow

            $table->string('currency', 10)->default('LKR');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'forecast_from', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_forecasts');
    }
};
