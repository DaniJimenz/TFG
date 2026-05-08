<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class PexelsService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, string $pexelsApiKey, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $pexelsApiKey;
        $this->logger = $logger;
    }

    /**
     * Busca una imagen en Pexels basada en la búsqueda
     * Retorna la URL de descarga de la imagen
     */
    public function searchImage(string $query): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                'headers' => [
                    'Authorization' => $this->apiKey,
                ],
                'query' => [
                    'query' => $query,
                    'per_page' => 1,
                    'size' => 'large',
                ]
            ]);

            $data = $response->toArray();
            
            if ($data['photos'] && count($data['photos']) > 0) {
                // Retornar URL de descarga de calidad alta
                return $data['photos'][0]['src']['large'];
            }
        } catch (\Exception $e) {
            // Registrar el error para facilitar el debugging
            $this->logger->error('Error en Pexels API (Image): ' . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * Busca un video en Pexels basado en la búsqueda
     * Retorna la URL del video (archivo MP4)
     */
    public function searchVideo(string $query): ?string
    {
        try {
            // Agregar palabras clave más específicas para ejercicios
            $searchTerms = [
                $query . ' exercise',
                $query . ' workout',
                $query . ' gym',
                'fitness ' . $query,
                'exercise ' . $query,
            ];

            foreach ($searchTerms as $term) {
                $response = $this->httpClient->request('GET', 'https://api.pexels.com/videos/search', [
                    'headers' => [
                        'Authorization' => $this->apiKey,
                    ],
                    'query' => [
                        'query' => $term,
                        'per_page' => 5,
                    ]
                ]);

                $data = $response->toArray();
                
                if ($data['videos'] && count($data['videos']) > 0) {
                    // Filtrar videos que NO sean naturales/agua/mar
                    foreach ($data['videos'] as $video) {
                        $title = strtolower($video['user']['name'] ?? '');
                        $description = strtolower($video['video_files'][0]['link'] ?? '');
                        
                        // Evitar videos genéricos de naturaleza
                        if (strpos($title, 'nature') === false && strpos($title, 'water') === false) {
                            if (!empty($video['video_files'])) {
                                usort($video['video_files'], function($a, $b) {
                                    return $b['height'] - $a['height'];
                                });
                                return $video['video_files'][0]['link'];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error en Pexels API (Video): ' . $e->getMessage());
            return null;
        }

        return null;
    }
}
