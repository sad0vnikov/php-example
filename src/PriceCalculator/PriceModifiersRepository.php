<?php

namespace VisaUK\PriceCalculator;


use VisaUK\Cart\Exceptions\CartException;
use VisaUK\PriceCalculator\Exceptions\PriceCalculatorException;
use VisaUK\TourPriceModifierModel;


class PriceModifiersRepository
{

    const INCLUDE_FLIGHT_CODE = 'include_flight';
    const SINGLE_SUPPLEMENT_CODE = 'single_supplement';
    const SOLO_SUPPLEMENT_CODE = 'solo_supplement';

    public function get_modifier_for_tour($tour_id, string $departure_date, $code)
    {
        $m = TourPriceModifierModel::query()
            ->whereHas('date_object',function($query) use ($departure_date, $tour_id) {
                $query->where('date', '=', $departure_date);
                $query->where('product_id', '=', $tour_id);
            })
           ->where('code', '=', $code)->first();
        return $m;
    }

    public function get_modifiers_for_tour($tour_id, string $departure_date, $codes=[], $categories=[], $check_invalid=false)
    {
        $date = (new ProductDatesRepository())->get_date_for_tour($tour_id, $departure_date);
        if (!$date) {
            throw new PriceCalculatorException("Got invalid date for tour $tour_id: $departure_date");
        }

        $valid_modifiers_objects = $date->price_modifiers;
        if ($codes) {
            $valid_modifiers_objects = $valid_modifiers_objects->filter(function($item) use ($codes) {
               return in_array($item->code, $codes);
            });
        }

        if ($categories) {
            $valid_modifiers_objects = $valid_modifiers_objects->filter(function($item) use ($categories) {
                return in_array($item->category, $categories);
            });
        }

        $valid_modifiers = [];
        foreach ($valid_modifiers_objects as $obj) {
            $valid_modifiers[$obj->code] = $obj;
        }
        if ($check_invalid) {
            $invalid_modifiers_keys = array_diff(
                $codes,
                array_keys($valid_modifiers)
            );
            if ($invalid_modifiers_keys) {
                throw new PriceCalculatorException(
                    'Following price modifiers are invalid: ' . implode(', ', $invalid_modifiers_keys)
                );
            }
        }
        return $valid_modifiers;
    }
}