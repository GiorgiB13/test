<?php

namespace App\Jobs;

use App\Events\CurrencySaved;
use App\Models\CurrencyHistory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckCurrencyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->fetchCurrencies();
        $recentCurrency = DB::table('currency_histories')
            ->latest()
            ->first();
        if (DB::table('currency_histories')->get()->count() === 1){
            event(new CurrencySaved($recentCurrency->currencies));
            return;
        }

        $penultimateCurrency = DB::table('currency_histories')
            ->orderBy('created_at', 'desc')
            ->skip(1)->take(1)
            ->first();

        if ($recentCurrency->currencies != $penultimateCurrency->currencies) {
            event(new CurrencySaved($recentCurrency->currencies));
        }
    }

    public function fetchCurrencies(): void
    {
        $response = Http::get(config('currency.currency_monitoring_url'));

        CurrencyHistory::insert([
            'currencies' => json_encode($response->body()),
            'created_at' => Carbon::now()
        ]);
    }
}
