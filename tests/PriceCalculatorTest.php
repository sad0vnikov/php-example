<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use VisaUK\PriceCalculator\PriceCalculator;
use VisaUK\PriceModifiers\FixedPriceModifierSingle;
use VisaUK\PriceModifiers\OptionsPriceModifier;
use VisaUK\TourDateModel;
use VisaUK\Tourist;
use VisaUK\TouristGroupRoom;
use VisaUK\TouristGroup;
use VisaUK\TourPriceModifierFilledSlots;
use VisaUK\TourPriceModifierModel;
use VisaUK\TourPriceModifierSlots;
use VisaUK\PriceModifiers\BooleanPriceModifierSingle;


final class PriceCalculatorTest extends TestCase
{

    public function test_no_price_modifiers()
    {

        $tour_date = new TourDateModel();
        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $tour_date->date = date("2021-05-10");


        $tourists = new TouristGroup([
            new TouristGroupRoom([new Tourist()]),
        ]);

        $calc = (new PriceCalculator([]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);


        $this->assertEquals(
            100,
            $calc->calculate_price()
        );
    }

    public function test_fixed_price_modifier_per_person()
    {

        $tour_date = new TourDateModel();

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);


        $tour_date->date = date("2021-05-10");

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->type = TourPriceModifierModel::TYPE_FIXED;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([new TouristGroupRoom([
            new Tourist(),
            new Tourist(),
            new Tourist(),
        ])]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);


        $this->assertEquals(
            600,
            $calc->calculate_price()
        );
    }

    public function test_fixed_price_modifier_per_group()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_package);
        $modifier_model->type = TourPriceModifierModel::TYPE_FIXED;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_GROUP;
        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([
                new Tourist(),
                new Tourist(),
                new Tourist(),
            ])
        ]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);


        $this->assertEquals(
            400,
            $calc->calculate_price()
        );
    }

    public function test_boolean_price_modifier()
    {

        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->id = 123;
        $modifier_model->type = TourPriceModifierModel::TYPE_BOOLEAN;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([new Tourist()]),
        ]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            100,
            $calc->calculate_price()
        );

        $calc->set_calculator_condition_value(
            $modifier_model->id,
            BooleanPriceModifierSingle::CONDITION_ARG,
            true);

        $this->assertEquals(
            200,
            $calc->calculate_price()
        );

        $calc->set_calculator_condition_value(
            $modifier_model->id,
            BooleanPriceModifierSingle::CONDITION_ARG,
            false);

        $this->assertEquals(
            100,
            $calc->calculate_price()
        );
    }

    public function test_select_price_modifier()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->id = 123;
        $modifier_model->type = TourPriceModifierModel::TYPE_SELECT;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->set_args([
            'options' => [
                'room_standard' => ['name' => 'Standard room'],
                'room_comfort' => ['name' => 'Comfort room'],
                'room_luxury' => ['name' => 'Luxury room'],
            ],
            'default_value' => 'room_standard',
        ]);

        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots([
            'room_standard' => 100,
            'room_comfort' => 200,
            'room_luxury' => 900,
        ]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([
                new Tourist(),
            ])
        ]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            200,
            $calc->calculate_price()
        );

        $calc->set_calculator_condition_value(
            $modifier_model->id,
            OptionsPriceModifier::CONDITION_ARG,
            'room_comfort'
        );

        $this->assertEquals(
            300,
            $calc->calculate_price()
        );

        $calc->set_calculator_condition_value(
            $modifier_model->id,
            OptionsPriceModifier::CONDITION_ARG,
            'room_luxury'
        );

        $this->assertEquals(
            1000,
            $calc->calculate_price()
        );
    }

    public function test_countable_price_modifier()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->id = 123;
        $modifier_model->type = TourPriceModifierModel::TYPE_FIXED;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->is_countable = True;

        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([
                new Tourist(),
            ])
        ]);

        $calc = (new PriceCalculator([]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            100,
            $calc->calculate_price(),
            "price without modifiers should match 100"
        );

        $quantity_price = [0 => 100, 1 => 200, 5 => 600];
        foreach ($quantity_price as $count => $price) {
            $calc = (new PriceCalculator([$modifier_model]))
                ->set_date($tour_date)
                ->set_tourists($tourists)
                ->set_package($tour_package);

            $calc->set_calculator_condition_value(
                $modifier_model->id,
                FixedPriceModifierSingle::QUANTITY_ARG,
                $count,
            );

            $this->assertEquals(
                $price,
                $calc->calculate_price(),
                "price with FixedModifier, quantity = $count"
            );
        }

    }


    public function test_countable_price_modifier_boolean()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->id = 123;
        $modifier_model->type = TourPriceModifierModel::TYPE_BOOLEAN;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->is_countable = True;

        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([
                new Tourist(),
            ])
        ]);

        $calc = (new PriceCalculator([]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            100,
            $calc->calculate_price(),
            "price without modifiers should match 100"
        );

        $quantity_price = [0 => 100, 1 => 200, 5 => 600];
        foreach ($quantity_price as $count => $price) {
            $calc = (new PriceCalculator([$modifier_model]))
                ->set_date($tour_date)
                ->set_tourists($tourists)
                ->set_package($tour_package);

            $calc->set_calculator_condition_value(
                $modifier_model->id,
                BooleanPriceModifierSingle::QUANTITY_ARG,
                $count,
            );

            $calc->set_calculator_condition_value(
                $modifier_model->id,
                BooleanPriceModifierSingle::CONDITION_ARG,
                true,
            );

            $this->assertEquals(
                $count,
                $calc->get_quantity_for_modifier(
                    $calc->get_price_modifier_from_model(
                        $modifier_model,
                    ),
                    $tourists
                ),
            );

            $this->assertEquals(
                $price,
                $calc->calculate_price(),
                "price with FixedModifier, quantity = $count"
            );

            $calc->set_calculator_condition_value(
                $modifier_model->id,
                BooleanPriceModifierSingle::CONDITION_ARG,
                false,
            );

            $this->assertEquals(
                100,
                $calc->calculate_price(),
                "price with FixedModifier, quantity = $count; only base price is counted"
            );
        }

    }

    public function test_combine_price_modifiers()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");
        $tour_date->base_price = 100;


        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $select_modifier = new TourPriceModifierModel();
        $select_modifier->date_object()->associate($tour_date);
        $select_modifier->id = 123;
        $select_modifier->type = TourPriceModifierModel::TYPE_SELECT;
        $select_modifier->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $select_modifier->set_args([
            'options' => [
                'room_standard' => ['name' => 'Standard room'],
                'room_comfort' => ['name' => 'Comfort room'],
                'room_luxury' => ['name' => 'Luxury room'],
            ],
            'default_value' => 'room_standard',
        ]);
        $select_modifier->set_own_prices(new TourPriceModifierFilledSlots([
            'room_standard' => 0,
            'room_comfort' => 200,
            'room_luxury' => 900,
        ]));

        $bool_modifier = new TourPriceModifierModel();
        $bool_modifier->date_object()->associate($tour_date);
        $bool_modifier->id = 124;
        $bool_modifier->type = TourPriceModifierModel::TYPE_BOOLEAN;
        $bool_modifier->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $bool_modifier->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([
            new TouristGroupRoom([
                new Tourist(),
            ])
        ]);

        $calc = (new PriceCalculator([$select_modifier, $bool_modifier]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $calc->set_calculator_condition_value(
            $bool_modifier->id,
            BooleanPriceModifierSingle::CONDITION_ARG,
            true,
        );

        $this->assertEquals(
            200, // base_price + bool_modifier
            $calc->calculate_price()
        );

        $calc->set_calculator_condition_value(
            $select_modifier->id,
            OptionsPriceModifier::CONDITION_ARG,
            'room_comfort'
        );

        $calc->set_calculator_condition_value(
            $bool_modifier->id,BooleanPriceModifierSingle::CONDITION_ARG,
            true
        );

        $this->assertEquals(
            400,
            $calc->calculate_price()
        );

    }

    public function test_single_supplement_price_modifier()
    {
        $tour_date = new TourDateModel();
        $tour_date->date = date("2021-05-10");

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->id = 123;
        $modifier_model->type = TourPriceModifierModel::TYPE_SINGLE_SUPPLEMENT;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_GROUP;
        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 500]));

        $room = new TouristGroupRoom([new Tourist()]);
        $room->set_type(TouristGroupRoom::ROOM_TYPE_DOUBLE);
        $tourists = new TouristGroup([
            $room,
        ]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            100,
            $calc->calculate_price()
        );


        $single_room = new TouristGroupRoom([new Tourist()]);
        $single_room->set_type(TouristGroupRoom::ROOM_TYPE_SINGLE);
        $tourists->add_subgroup($single_room);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);

        $this->assertEquals(
            700, // two base price passengers + single supplement
            $calc->calculate_price()
        );

    }

    public function test_advanced_pricing()
    {
        $tour_date = new TourDateModel();
        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_date->pricing_type = \VisaUK\TourPackage::TOUR_PRICING_ADVANCED;
        $tour_package->base_price = 50;
        $tour_package->advanced_prices = [
            "price_2" => 50,
            "price_2_discounted" => 500, // price before discount
            "price_4" => 100,
            "price_4_discounted" => 1000,
            "price_6" => 200,
            "price_6_discounted" => 2000,
            "price_8" => 300,
            "price_8_discounted" => 3000,
            "price_10" => 400,
            "price_10_discounted" => 4000,
        ];
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $tour_date->date = date("2021-05-10");

        $expected_prices = [
            1 => 50,
            2 => 100,
            3 => 300,
            4 => 400,
            5 => 1000,
            6 => 1200,
            10 => 4000,
        ];
        $expected_prices_discounted = [
            1 => 500,
            2 => 1000,
            3 => 3000,
            4 => 4000,
            5 => 10000,
            6 => 12000,
            10 => 40000,
        ];
        foreach ($expected_prices as $tourists_cnt => $expected_price) {
            $tourists_array = [];
            for ($i = 0; $i < $tourists_cnt; $i++) {
                $tourists_array[] = new Tourist();
            }
            $tourists = new TouristGroup([
                new TouristGroupRoom($tourists_array),
            ]);

            /**
             * GIVEN: 1 tourist
             * EXPECTED: base price
             */
            $calc = (new PriceCalculator([]))
                ->set_date($tour_date)
                ->set_tourists($tourists)
                ->set_package($tour_package);

            $this->assertEquals(
                $expected_price,
                $calc->calculate_price(),
                "Price for $tourists_cnt persons"
            );

            $this->assertEquals(
                $expected_prices_discounted[$tourists_cnt],
                $calc->calculate_price_before_discount(),
                "Discounted price for $tourists_cnt persons"
            );
        }

    }


    public function test_package_price_modifier()
    {
        /**
         * Модификаторы, привязанные к пакету
         */
        $tour_date = new TourDateModel();

        $tour_package = new \VisaUK\TourPackage();
        $tour_package->id = 42;
        $tour_date->default_package_id = 42;
        $tour_package->base_price = 100;
        $tour_package->name = 'Basic package';
        $tour_package->date_object()->associate($tour_date);

        $tour_package2 = new \VisaUK\TourPackage();
        $tour_package2->base_price = 200;
        $tour_package2->name = 'Another package';
        $tour_package2->date_object()->associate($tour_date);


        $tour_date->date = date("2021-05-10");

        $modifier_model = new TourPriceModifierModel();
        $modifier_model->date_object()->associate($tour_date);
        $modifier_model->package_object()->associate($tour_package);
        $modifier_model->type = TourPriceModifierModel::TYPE_FIXED;
        $modifier_model->price_per = TourPriceModifierModel::PRICE_PER_PERSON;
        $modifier_model->set_own_prices(new TourPriceModifierFilledSlots(['price' => 100]));

        $tourists = new TouristGroup([new TouristGroupRoom([
            new Tourist(),
            new Tourist(),
            new Tourist(),
        ])]);

        $calc = (new PriceCalculator([$modifier_model]))
            ->set_date($tour_date)
            ->set_tourists($tourists)
            ->set_package($tour_package);


        $this->assertEquals(
            600,
            $calc->calculate_price()
        );
    }

}