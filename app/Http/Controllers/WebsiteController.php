<?php

namespace App\Http\Controllers;

use App\Mail\NewSellerAttendance;
use App\Models\EventDetail;
use App\Models\Wordpress\PostMetadata;
use App\Models\Wordpress\Posts;
use App\Models\WorkTime;
use App\Providers\ActiveCampaignProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class WebsiteController extends Controller
{
    public function newSellerAttendance(Request $request)
    {
        $seller = $this->getAvailableSeller();
        $metadata = PostMetadata::where('post_id', $seller->id)
            ->where('meta_key', 'NOT LIKE', '\_%')
            ->get();

        $metadata = $metadata->mapWithKeys(function ($item) {
            return [$item->meta_key => $item->meta_value];
        });

        $customer = (object) [
            'name' => $request->get('nome'),
            'email' => $request->get('email'),
            'phone' => $request->get('telefone'),
            'format' => $request->get('formato'),
            'area' => $request->get('area-presencial', $request->get('area-online')),
            'course' => $request->get('curso'),
        ];

        Mail::to($metadata['email'])->send(new NewSellerAttendance($customer));

        $ACProvider = new ActiveCampaignProvider();

        $nameBroken = explode(' ', $customer->name, 2);
        $contact = $ACProvider->post('/contact/sync', [
            'contact' => [
                'email'                     => $customer->email,
                'first_name'                => $nameBroken[0],
                'last_name'                 => isset($nameBroken[1]) ? $nameBroken[1] : '',
                'phone'                     => $customer->phone,
                'fieldValues' => [
                    ['field' => 6, 'value' => $customer->format],
                    ['field' => 7, 'value' => ucwords(str_replace('-', ' ', $customer->area))],
                    ['field' => 11, 'value' => $customer->course],
                ],
            ]
        ]);

        $ACProvider->post('/deals', [
            'deal' => [
                'title' => "{$customer->course} - {$customer->format}",
                'owner' => $metadata['id-active-campaign'],
                'currency' => "brl",
                'percent' => null,
                'value' => 0,
                'description' => "Novo Negócio",
                'contact' => $contact['contact']['id'],
                'account' => null,
                'group' => "2",
                'stage' => "10",
                'status' => 0
            ]
        ]);

        return response()->json([
            'success' => true,
            'whatsapp' => $metadata['numeros_whatsapp'],
        ]);
    }

    private function getAvailableSeller()
    {
        // Checa se existe algum evento neste horário para todos vendedores
        $hasEventNow = EventDetail::whereNull('seller_id')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start', '<', now())
                        ->where('end', '>', now());
                })
                    ->orWhere(function ($query) {
                        $query->where('all_day', 1)
                            ->whereDate('start', now());
                    });
            })
            ->exists();

        $sellers = Posts::sellers()->select([
            'ID as id',
            'post_title as name',
        ]);

        if ($hasEventNow) {
            return $sellers->inRandomOrder()->first();
        }

        // Obtem os eventos direcionado aos vendedores
        $sellersAway = EventDetail::whereNotNull('seller_id')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start', '<', now())
                        ->where('end', '>', now());
                })
                    ->orWhere(function ($query) {
                        $query->where('all_day', 1)
                            ->whereDate('start', now());
                    });
            })
            ->get()->pluck('seller_id');

        // Obtem os vendedores que estão trabalhando
        $sellersWorking = WorkTime::where('day_of_week', now()->dayOfWeek)
            ->where('start_time', '<', now())
            ->where('end_time', '>', now())
            ->whereNotIn('seller_id', $sellersAway)
            ->get()->pluck('seller_id');

        if ($sellersWorking->count() == 0) {
            return $sellers->inRandomOrder()->first();
        }

        return $sellers->whereIn('ID', $sellersWorking)->inRandomOrder()->first();
    }
}
