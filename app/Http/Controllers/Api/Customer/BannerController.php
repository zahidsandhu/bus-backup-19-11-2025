<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\BannerStatusEnum;
use App\Enums\BannerTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    /**
     * Get active banners for the mobile application.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'type' => ['nullable', 'string', Rule::in(BannerTypeEnum::getTypes())],
        ]);

        $query = Banner::query()
            ->select('id', 'title', 'type', 'path', 'status', 'created_at', 'order')
            ->where('status', BannerStatusEnum::ACTIVE->value);

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $banners = $query
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Banner $banner) {
                $typeValue = $banner->type instanceof BannerTypeEnum ? $banner->type->value : $banner->type;
                $statusValue = $banner->status instanceof BannerStatusEnum ? $banner->status->value : $banner->status;

                $imageUrl = $banner->path
                    ? (Storage::disk('public')->exists($banner->path)
                        ? Storage::url($banner->path)
                        : null)
                    : null;

                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $typeValue,
                    'type_name' => BannerTypeEnum::getTypeName($typeValue),
                    'image_url' => $imageUrl,
                    'status' => $statusValue,
                    'status_name' => BannerStatusEnum::getStatusName($statusValue),
                    'order' => $banner->order,
                    'created_at' => $banner->created_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
            ],
            'message' => 'Banners fetched successfully',
        ]);
    }
}


