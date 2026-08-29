<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use App\Models\Addresses\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AddressController extends Controller
{
    public function indexProvinces(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $cacheKey = 'addresses:provinces:'.$locale;

        $payload = Cache::remember($cacheKey, 86400, function () use ($locale) {
            $rows = Province::query()
                ->orderBy('type')
                ->orderBy($locale === 'km' ? 'name_km' : 'name_en')
                ->get();

            return $rows->map(fn ($p) => [
                'value' => $p->id,
                'code' => $p->code,
                'label' => $locale === 'km' ? $p->name_km : $p->name_en,
                'label_km' => $p->name_km,
            ])->all();
        });

        return response()->json(['data' => $payload]);
    }

    public function indexDistricts(Request $request, Province $province): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($province->districts(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($d) => [
                'value' => $d->id,
                'code' => $d->code,
                'label' => $locale === 'km' ? $d->name_km : $d->name_en,
                'label_km' => $d->name_km,
            ])->all(),
        ]);
    }

    public function indexCommunes(Request $request, District $district): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($district->communes(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($c) => [
                'value' => $c->id,
                'code' => $c->code,
                'label' => $locale === 'km' ? $c->name_km : $c->name_en,
                'label_km' => $c->name_km,
            ])->all(),
        ]);
    }

    public function indexVillages(Request $request, Commune $commune): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $rows = $this->filteredChildren($commune->villages(), $request, $locale);

        return response()->json([
            'data' => $rows->map(fn ($v) => [
                'value' => $v->id,
                'code' => $v->code,
                'label' => $locale === 'km' ? $v->name_km : $v->name_en,
                'label_km' => $v->name_km,
            ])->all(),
        ]);
    }

    private function filteredChildren($relation, Request $request, string $locale)
    {
        $q = $relation->newQuery();
        if ($search = $request->string('q')->toString()) {
            $q->where(function ($w) use ($search) {
                $w->where('name_en', 'like', '%'.$search.'%')
                  ->orWhere('name_km', 'like', '%'.$search.'%');
            });
        }
        return $q->orderBy($locale === 'km' ? 'name_km' : 'name_en')->get();
    }

    private function resolveLocale(Request $request): string
    {
        $al = $request->header('Accept-Language', 'en');
        return str_starts_with($al, 'km') ? 'km' : 'en';
    }
}
