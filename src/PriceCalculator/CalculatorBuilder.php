<?php

namespace VisaUK\PriceCalculator;

use VisaUK\PriceCalculator\Exceptions\PriceCalculatorException;
use VisaUK\TouristGroup;

class CalculatorBuilder
{
    public function make_tour_calculator($tour_id, string $departure_date, TouristGroup $tourists, $checkout_options)
    {
        $price_modifiers = (new PriceModifiersRepository())->get_modifiers_for_tour(
            $tour_id,
            $departure_date,
            [],
            [],
            false
        );
        $calculator = new PriceCalculator($price_modifiers);
        $date_obj = (new ProductDatesRepository())->get_date_for_tour($tour_id, $departure_date);
        $price_modifiers = $date_obj->price_modifiers;
        $package_type_value = $checkout_options['package_type'] ? $checkout_options['package_type']['value'] : null;
        $package = $date_obj->get_package($package_type_value);
        if (!$package) {
            $package = $date_obj->default_package;
        }

        if (!$package) {
            throw new PriceCalculatorException(
                'Default package not found for this tour; Package should be specified'
            );
        }

        $calculator
            ->set_date($date_obj)
            ->set_tourists($tourists)
            ->set_package($package);

        foreach ($price_modifiers as $modifier) {
            $condition_values = $checkout_options[$modifier->code] ?? [];
            foreach ($condition_values as $condition_name => $condition_value) {
                $calculator->set_calculator_condition_value(
                    $modifier->id,
                    $condition_name,
                    $condition_value
                );
            }
        }
        return $calculator;
    }
}