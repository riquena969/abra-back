<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Providers\ActiveCampaignProvider;

class CobreFacilController extends Controller
{
    public function webhook(Request $request)
    {
        if ($request->get('event') != 'invoice.paid') {
            return response()->json(['message' => 'Event ignored. It is not invoice.paid.'], 200);
        }

        $data = $request->get('data');

        $email = $data['customer']['email'];

        $ac = new ActiveCampaignProvider();

        $contact = $ac->get('/contacts', [
            'email' => $email,
        ]);

        if (!$contact['meta']['total']) {
            return response()->json(['message' => 'Contact not found.'], 200);
        }

        $contact = $contact['contacts'][0];
        $contactId = $contact['id'];

        $tags = $ac->get("/contacts/{$contactId}/contactTags")['contactTags'];

        $tagRescisaoPendenteId = null;
        foreach ($tags as $tag) {
            if ($tag['tag'] == 460) {
                $tagRescisaoPendenteId = $tag['id'];
                break;
            }
        }

        if ($tagRescisaoPendenteId === null) {
            return response()->json(['message' => 'Contact does not have the tag "rescisao-pendente".'], 200);
        }

        $contactId = $contact['id'];

        $tag_remove = $ac->delete("/contactTags/{$tagRescisaoPendenteId}");

        $tag_add = $ac->post("/contactTags", [
            'contactTag' => [
                'contact' => $contactId,
                'tag' => 461 // rescisao-paga
            ]
        ]);

        return [
            'contact_id' => $contactId,
            'tag_remove' => $tag_remove,
            'tag_add' => $tag_add,
        ];
    }
}
