<?php

namespace VisaUK\PriceCalculator;

use VisaUK\Cart\OrderHelper;
use VisaUK\PriceCalculator\Exceptions\PriceCalculatorException;
use VisaUK\PriceModifiers;
use VisaUK\TourDateModel;
use VisaUK\Tourist;
use VisaUK\TouristGroup;
use VisaUK\TourPackage;
use VisaUK\TourPriceModifierFactory;
use VisaUK\TourPriceModifierFilledSlots;

class PriceCalculator
{

    /**
     * @var PriceModifiers\BasePriceModifier[]
     */
    protected $price_modifiers = [];

    /**
     * @var TourDateModel
     */
    protected $tour_date;

    /**
     * @var TouristGroup
     */
    protected $tourists;

    /**
     * @var TourPackage
     */
    protected $tour_package;


    public function __construct($price_modifier_models)
    {
        $modifiers_factory = new TourPriceModifierFactory();

        foreach ($price_modifier_models as $m) {
            $this->price_modifiers[$m->id] = $modifiers_factory->get_price_modifier($m);
        }
    }

    protected function calculate_using_price_per_person($base_price)
    {
        if (!$this->tour_package) {
            throw new PriceCalculatorException('No package was chosen');
        }

        if (!$this->tour_date) {
            throw new PriceCalculatorException('No date was chosen');
        }

        if (!$this->tourists) {
            throw new PriceCalculatorException('No tourists were provided');
        }

        $price = $base_price * $this->tourists->count();
        foreach (array_values($this->price_modifiers) as $modifier) {
            $price = $modifier->modify_price($price, $this->tourists);
        }

        return $price;
    }

    public function calculate_price()
    {
        $price_per_person = $this->get_price_per_person();
        return $this->calculate_using_price_per_person($price_per_person);
    }

    public function get_price_per_person()
    {
        $pricing_type = $this->tour_package->get_pricing_type();
        switch ($pricing_type) {
            case TourPackage::TOUR_PRICING_BASIC:
                return $this->tour_package->base_price;
            case TourPackage::TOUR_PRICING_ADVANCED:
                $price = $this->get_advanced_base_price();
                return $price;
            default:
                throw new \ValueError("Unsupported pricing type $pricing_type");
        }
    }

    public function calculate_price_before_discount()
    {
        $pricing_type = $this->tour_package->get_pricing_type();
        switch ($pricing_type) {
            case TourPackage::TOUR_PRICING_BASIC:
                if (!$this->tour_package->base_price_discounted) {
                    return null;
                }
                return $this->calculate_using_price_per_person($this->tour_package->base_price_discounted);
            case TourPackage::TOUR_PRICING_ADVANCED:
                $price = $this->get_advanced_base_price(true);
                return $this->calculate_using_price_per_person($price);
            default:
                throw new \ValueError("Unsupported pricing type $$pricing_type");
        }

    }

    public function get_price_modifier_from_model($price_modifier_model)
    {
        return $this->price_modifiers[$price_modifier_model->id];
    }


    public function get_extra_fee_for_modifier(PriceModifiers\BasePriceModifier $modifier)
    {
        return $modifier->get_extra_fee();
    }


    public function get_total_fee_for_modifier(PriceModifiers\BasePriceModifier $modifier)
    {
        return $modifier->get_extra_fee_total($this->tourists);
    }


    public function get_quantity_for_modifier(PriceModifiers\BasePriceModifier $modifier, TouristGroup $group)
    {
        return $modifier->get_quantity($group);
    }

    public function set_calculator_condition_value($modifier_id, $condition_name, $condition_value)
    {
        $this->price_modifiers[$modifier_id]->set_condition_value($condition_name, $condition_value);
    }

    public function set_date(TourDateModel $date)
    {
        $this->tour_date = $date;
        $this->tour_package = $date->default_package;
        return $this;
    }

    public function set_tourists(TouristGroup $tourists)
    {
        $this->tourists = $tourists;
        return $this;
    }

    public function set_package(TourPackage $package)
    {
        $this->tour_package = $package;
        return $this;
    }


    public function get_package(): ?TourPackage
    {
        return $this->tour_package;
    }

    /**
     * @return PriceModifiers\BasePriceModifier[]
     */
    public function get_modifiers()
    {
        return array_values($this->price_modifiers);
    }

    public function get_deposit_price()
    {
        return $this->calculate_price() * ($this->get_deposit_size_percents() / 100);
    }

    public function get_deposit_size_percents()
    {
        return OrderHelper::get_deposit_size_percents();
    }
}