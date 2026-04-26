<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo\Manager;

use Modules\Seo\SeoTrait;
use Spatie\SchemaOrg\Schema;

class SeoModel {

    use SeoTrait;

    protected array $schema = [];

    public function getMeta(){
        // comment explaining why the method is empty
    }

    /**
     * @return string
     */
    public function getSchema(): string {
        $scriptSchema = "";
        foreach($this->schema as $type => $schema){
            $scriptSchema.=$schema."\n";
        }
        return $scriptSchema;
    }

    public function getMedia(){
        // comment explaining why the method is empty
    }

    /**
     * @param string $type
     * @param string $schema
     * @return void
     */
    public function setSchema(string $type, string $schema): void {
        $this->schema[$type] = $schema;
    }


    /**
     * @param array $data
     * @param array $breadcrumbs
     * @param array $options
     * @return void
     */
    public function buildJsonLd(array $data, array $breadcrumbs = [], array $options = []): void {
        $orgType = $options['orgType'] ?? 'LocalBusiness'; // e.g. AutoDealer, AutomotiveBusiness, LocalBusiness
        $websiteName = $options['websiteName'] ?? ($data['name'] ?? '');
        $baseUrl = rtrim((string)($data['url'] ?? ''), '/') . '/';

        $address = $data['address'] ?? [];
        $geo = $data['geo'] ?? [];

        // IDs for graph linking
        $websiteId = $baseUrl . '#website';
        $orgId = $baseUrl . '#organization';
        $storeId = $baseUrl . '#localbusiness';

        // Website
        $website = Schema::webSite()
            ->identifier($websiteId)
        ->url($baseUrl)
        ->name($websiteName)
        ->publisher(Schema::organization()->identifier($orgId));

        $this->setSchema("website", $website->toScript());

        // Optional: site search (Sitelinks Search Box)
        /*if (!empty($options['searchTargetUrl'])) {
            $website->potentialAction(
                Schema::searchAction()
                    ->target($options['searchTargetUrl'])
                    ->queryInput('required name=search_term_string')
            );
        }*/

        // Organization
        $organization = Schema::organization()
            ->identifier($orgId)
            ->name($data['name'] ?? '')
            ->url($baseUrl)
            ->logo($data['image'] ?? null)
            ->image($data['image'] ?? null)
            ->sameAs($data['sameAs'] ?? []);

        $this->setSchema("organization", $organization->toScript());

        // LocalBusiness / AutoDealer etc.
        $localBusiness = Schema::{$orgType}()
            ->identifier($storeId)
            ->name($data['name'] ?? '')
            ->url($baseUrl)
            ->image($data['image'] ?? null)
            ->telephone($data['telephone'] ?? null)
            ->sameAs($data['sameAs'] ?? [])
            ->address(
                Schema::postalAddress()
                    ->streetAddress($address['streetAddress'] ?? null)
                    ->addressLocality($address['addressLocality'] ?? null)
                    ->postalCode($address['postalCode'] ?? null)
                    ->addressCountry($address['addressCountry'] ?? null)
            )
            ->geo(
                Schema::geoCoordinates()
                    ->latitude($geo['latitude'] ?? null)
                    ->longitude($geo['longitude'] ?? null)
            )
            // Link business <-> organization
            ->parentOrganization(Schema::organization()->identifier($orgId));

        // openingHoursSpecification
        if (!empty($data['openingHoursSpecification']) && is_array($data['openingHoursSpecification'])) {
            $ohSpecs = [];
            foreach ($data['openingHoursSpecification'] as $spec) {
                $days = $spec['dayOfWeek'] ?? null;
                // allow string or array
                $days = is_array($days) ? $days : ($days ? [$days] : []);
                $daysEnum = array_values(array_filter(array_map(
                    fn($d) => is_string($d) ? ('https://schema.org/' . $d) : null,
                    $days
                )));

                $oh = Schema::openingHoursSpecification()
                    ->opens($spec['opens'] ?? null)
                    ->closes($spec['closes'] ?? null);

                if (!empty($daysEnum)) {
                    $oh->dayOfWeek($daysEnum);
                }

                $ohSpecs[] = $oh;
            }
            $localBusiness->openingHoursSpecification($ohSpecs);
        }

        $this->setSchema("localBusiness", $localBusiness->toScript());

        // BreadcrumbList (optional)
        $breadcrumb = null;
        if (!empty($breadcrumbs)) {
            $items = [];
            $pos = 1;

            foreach ($breadcrumbs as $crumb) {
                $name = $crumb['name'] ?? null;
                $url = $crumb['url'] ?? null;

                if (!$name || !$url) {
                    continue;
                }

                $items[] = Schema::listItem()
                    ->position($pos++)
                    ->name($name)
                    ->item($url);
            }

            if (!empty($items)) {
                $breadcrumb = Schema::breadcrumbList()
                    ->identifier($baseUrl . '#breadcrumb')
                    ->itemListElement($items);

                $this->setSchema("breadcrumb", $breadcrumb->toScript());
            }
        }
    }

    public function schemaVehicleList(string $pageUrl, array $cars, array $breadcrumbs = []): void {
        $pageUrl = rtrim($pageUrl, '/');

        $items = [];
        $pos = 1;

        foreach ($cars as $c) {
            $carUrl = (string)($c['url'] ?? '');
            if ($carUrl === '') {
                continue;
            }

            /*$carId = $carUrl . '#car';

            $offer = null;
            if (!empty($c['price'])) {
                $offer = Schema::offer()
                    ->priceCurrency($c['currency'] ?? 'EUR')
                    ->price((string)$c['price'])
                    ->url($carUrl)
                    ->availability($c['availability'] ?? 'https://schema.org/InStock');
            }*/

            // Car/Vehicle node (kept light for listing)
            /*$carNode = Schema::car()
                ->identifier($carId)
                ->url($carUrl)
                ->name($c['title'] ?? ($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))
                ->image($c['images'] ?? null)
                ->brand(!empty($c['make']) ? Schema::brand()->name($c['make']) : null)
                ->model($c['model'] ?? null)
                ->offers($offer);*/

            // Optional quick facts
            /*if (!empty($c['mileage'])) {
                $carNode->mileageFromOdometer(
                    Schema::quantitativeValue()
                        ->value((float)$c['mileage'])
                        ->unitCode('KMT')
                );
            }
            if (!empty($c['firstRegistration'])) {
                // Use ISO date or YYYY-MM; keep as text if you have only "01/2025"
                $carNode->dateVehicleFirstRegistered($c['firstRegistration']);
            }
            if (!empty($c['fuel'])) {
                $carNode->fuelType((string)$c['fuel']);
            }
            if (!empty($c['transmission'])) {
                $carNode->vehicleTransmission((string)$c['transmission']);
            }*/
            /*if (!empty($c['seller_inventory_key'])) {
                $carNode->sku((string)$c['seller_inventory_key']);
            }*/
            /*if (!empty($c['gtin'])) {
                $carNode->sku((string)$c['gtin']); // if it’s not EAN13, remove this
            }*/

            $items[] = Schema::listItem()
                ->position($pos++)
                ->url($carUrl)
                /*->item($carNode)*/;
        }

        if (empty($items)) {
            return;
        }

            $itemList = Schema::itemList()
                ->identifier($pageUrl . '#itemlist')
            ->url($pageUrl)
            ->name('Fahrzeugbestand')
            ->itemListOrder('https://schema.org/ItemListUnordered')
            ->numberOfItems(count($items))
            ->itemListElement($items);

        $breadcrumb = null;
        if (!empty($breadcrumbs)) {
            $crumbItems = [];
            $p = 1;
            foreach ($breadcrumbs as $cr) {
                if (empty($cr['name']) || empty($cr['url'])) continue;
                $crumbItems[] = Schema::listItem()->position($p++)->name($cr['name'])->item($cr['url']);
            }
            if ($crumbItems) {
                $breadcrumb = Schema::breadcrumbList()
                    ->identifier($pageUrl . '#breadcrumb')
                    ->itemListElement($crumbItems);
                $this->setSchema("breadcrumb", $breadcrumb->toScript());
            }
        }

        $this->setSchema("itemlist", $itemList->toScript());
        //return Schema::graph(array_values(array_filter([$itemList, $breadcrumb])))->toScript();
    }

    public function schemaVehicleDetail(string $carUrl, array $c, array $breadcrumbs = []): void {
        $carUrl = rtrim($carUrl, '/');
        $carId  = $carUrl . '#car';

        $parsedUrl = parse_url($carUrl);
        $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '') . '/';

        $offer = null;
        if (!empty($c['price'])) {
            $condition = Schema::offerItemCondition()::NewCondition;;
            if (isset($c['firstRegistration'])){
                $condition = Schema::offerItemCondition()::UsedCondition;
            }

            $sellerId = $baseUrl . '#organization';
            $offer = Schema::offer()
                ->priceCurrency($c['currency'] ?? 'EUR')
                ->price((string)$c['price'])
                ->url($carUrl)
                ->seller(Schema::organization()->identifier($sellerId))
                //->shippingDetails(Schema::offerShippingDetails())
                //->hasMerchantReturnPolicy(Schema::merchantReturnPolicy())
                ->availability(Schema::itemAvailability()::InStock)
                ->itemCondition($condition); // or NewCondition
        }

        $car = Schema::product()
            ->identifier($carId)
            ->url($carUrl)
            ->name($c['title'] ?? (($c['make'] ?? '') . ' ' . ($c['model'] ?? '')))
            ->description($c['description'] ?? ($c['title'] ?? ''))
            ->image($c['images'] ?? null)
            ->brand(!empty($c['make']) ? Schema::brand()->name($c['make']) : null)
            ->model($c['model'] ?? null)
            ->color($c['color'] ?? null)
            //->vehicleTransmission($c['transmission'] ?? null)
            //->fuelType($c['fuel'] ?? null)
            ->additionalType('https://schema.org/Car')
            ->offers($offer);

        // identifiers
        if (!empty($c['gtin']))  $car->sku((string)$c['gtin']);
        //if (!empty($c['vin']))  $car->vehicleIdentificationNumber((string)$c['vin']);

        // mileage
        if (!empty($c['mileage'])) {
            $car->additionalProperty([
                Schema::propertyValue()->name('mileageFromOdometer')->value((string)$c['mileage'] . ' km'),
            ]);

            /*$car->mileageFromOdometer(
                Schema::quantitativeValue()
                    ->value((float)$c['mileage'])
                    ->unitCode('KMT')
            );*/
        }

        // first registration (ISO recommended, but you can pass "01/2025" as text)
        if (!empty($c['firstRegistration'])) {
            $car->additionalProperty([
                Schema::propertyValue()->name('dateVehicleFirstRegistered')->value((string)$c['firstRegistration']),
            ]);
            //$car->dateVehicleFirstRegistered($c['firstRegistration']);
        }

        if (!empty($c['fuel'])) {
            $car->additionalProperty([
                Schema::propertyValue()->name('fuelType')->value((string)$c['fuel']),
            ]);
        }

        if (!empty($c['transmission'])) {
            $car->additionalProperty([
                Schema::propertyValue()->name('vehicleTransmission')->value((string)$c['transmission']),
            ]);
        }

        // power (optional)
        /*if (!empty($c['powerKW'])) {
            $car->vehicleEngine(
                Schema::engineSpecification()
                    ->enginePower([
                        Schema::quantitativeValue()
                            ->value((float)$c['powerKW'])
                            ->unitText('kW'),
                        Schema::quantitativeValue()
                            ->value((float)$c['powerPS'])
                            ->unitText('PS')
                    ])
            );
        }*/

        $this->setSchema("car", $car->toScript());

        // Breadcrumbs
        $breadcrumb = null;
        if (!empty($breadcrumbs)) {
            $crumbItems = [];
            $p = 1;
            foreach ($breadcrumbs as $cr) {
                if (empty($cr['name']) || empty($cr['url'])) continue;
                $crumbItems[] = Schema::listItem()->position($p++)->name($cr['name'])->item($cr['url']);
            }
            if ($crumbItems) {
                $breadcrumb = Schema::breadcrumbList()
                    ->identifier($carUrl . '#breadcrumb')
                    ->itemListElement($crumbItems);
                $this->setSchema("breadcrumb", $breadcrumb->toScript());
            }
        }

        //return Schema::graph(array_values(array_filter([$car, $offer, $breadcrumb])))->toScript();
    }
}
