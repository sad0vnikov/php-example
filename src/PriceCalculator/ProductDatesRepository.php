<?php

namespace VisaUK\PriceCalculator;

use Carbon\Carbon;
use VisaUK\TourDateModel;


class ProductDatesRepository
{

    public function get_nearest_date($tour_id): ?TourDateModel
    {
        return TourDateModel::query()
            ->with('price_modifiers', 'packages')
            ->where(
                'product_id', $tour_id
            )->whereRaw(
                'date >= NOW()'
            )->orderBy('date')->first();
    }

    public function get_date_for_tour($tour_id, $date): ?TourDateModel
    {
        return TourDateModel::query()
            ->with('price_modifiers', 'packages')
            ->where(
                'product_id', $tour_id
            )->whereDate(
                'date', '=', $date
            )->first();
    }

    public function get_dates_range($tour_id, $date_from, $date_to)
    {
        return TourDateModel::query()
            ->with('price_modifiers', 'packages')
            ->where(
                'product_id', $tour_id
            )->whereDate(
                'date', '>=', $date_from
            )->whereDate(
                'date', '<', $date_to,
            )->get();
    }

    public function get_available_dates_for_tour($tour_id)
    {

        return TourDateModel::query()
            ->with('price_modifiers', 'packages')
            ->where('product_id', $tour_id)
            ->where('date', '>=', Carbon::now())
            ->get();
    }

}