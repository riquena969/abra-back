<?php

namespace App\Providers;
use Illuminate\Support\Facades\Http;

class ActiveCampaignProvider
{
    public function __construct()
    {
        $this->url = env('ACTIVECAMPAIGN_URL');
        $this->key = env('ACTIVECAMPAIGN_KEY');
    }

    public function get($path, $params = [])
    {
        $url = $this->url . $path;

        $response = Http::withHeaders([
            'Api-Token' => $this->key,
        ])->get($url, $params);

        return $response->json();
    }

    public function post($path, $data = [])
    {
        $url = $this->url . $path;

        $response = Http::withHeaders([
            'Api-Token' => $this->key,
        ])->post($url, $data);

        return $response->json();
    }

    public function put($path, $data = [])
    {
        $url = $this->url . $path;

        $response = Http::withHeaders([
            'Api-Token' => $this->key,
        ])->put($url, $data);

        return $response->json();
    }

    public function delete($path, $data = [])
    {
        $url = $this->url . $path;

        $response = Http::withHeaders([
            'Api-Token' => $this->key,
        ])->delete($url, $data);

        return $response->json();
    }
}
