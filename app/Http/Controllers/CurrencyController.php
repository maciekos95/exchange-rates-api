<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * Create a new CurrencyController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role_or_permission:editor|add-currency-rates', ['only' => ['add']]);
        $this->middleware('role_or_permission:admin|update-currency-rates', ['only' => ['update']]);
        $this->middleware('role_or_permission:admin|delete-currency-rates', ['only' => ['delete']]);
        $this->middleware('role_or_permission:user|get-currency-rates', ['only' => ['list', 'get']]);
    }

    /**
     * Add a currency exchange rate for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required', Rule::in(array_map('strtolower', ['EUR', 'USD', 'GBP'])),
            'date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (Carbon::parse($request->date)->isFuture()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot add currency exchange rate for a future date.',
            ], 422);
        }

        $currency = Currency::where('code', $request->code)->where('date', $request->date)->first();

        if ($currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'This currency exchange rate for the given date exists already in the database.',
                'currency' => $currency,
            ], 409);
        }

        $currency = new Currency();
        $currency->code = strtoupper($request->code);
        $currency->date = $request->date;
        $currency->amount = $request->amount;
        $currency->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully added currency exchange rate.',
            'currency' => $currency,
        ], 201);
    }

    /**
     * Update an existing currency exchange rate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code, $date)
    {
        $validator = Validator::make(['code' => $code, 'date' => $date], [
            'code' => Rule::in(array_map('strtolower', ['EUR', 'USD', 'GBP'])),
            'date' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = Currency::where('code', $code)->where('date', $date)->first();

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested currency exchange rate not found in the database.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency->amount = $request->amount;
        $currency->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully updated currency exchange rate.',
            'currency' => $currency,
        ]);
    }

    /**
     * Delete existing currency exchange rate.
     *
     * @param  string  $code
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public function delete($code, $date)
    {
        $validator = Validator::make(['currency' => $code, 'date' => $date], [
            'code' => Rule::in(array_map('strtolower', ['EUR', 'USD', 'GBP'])),
            'date' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = Currency::where('code', $code)->where('date', $date)->first();

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested currency exchange rate not found in the database.'
            ], 404);
        }

        $currency->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully deleted currency exchange rate.',
            'currency' => $currency,
        ]);
    }

    /**
     * Get the currency exchange rates for a specific date.
     *
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public function list($date)
    {
        $validator = Validator::make(['date' => $date], [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $currencies = Currency::whereDate('date', $date)->select(['code', 'date', 'amount'])
            ->orderByRaw("FIELD(code, 'EUR', 'USD', 'GBP')")->get();

        if ($currencies->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency exchange rates for the given date not found in the database.',
            ], 404);
        }

        return response()->json($currencies);
    }

    /**
     * Get the currency exchange rate for a specific currency code and date.
     *
     * @param  string  $code
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public function get($code, $date)
    {
        $validator = Validator::make(['code' => $code, 'date' => $date], [
            'code' => Rule::in(array_map('strtolower', ['EUR', 'USD', 'GBP'])),
            'date' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = Currency::where('code', $code)->whereDate('date', $date)
            ->select(['code', 'date', 'amount'])->first();

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested currency exchange rate not found in the database.'
            ], 404);
        }

        return response()->json($currency);
    }
}
