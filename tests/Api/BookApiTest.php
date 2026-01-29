<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookApiTest extends WebTestCase
{
    // test 1 : vérification de la liste des livres (version v1)
    public function testGetBooksCollection(): void
    {
        $client = static::createClient();
        
        // appel de l'API sur la nouvelle URL v1
        $client->request('GET', '/api/v1/books');

        // vérification du statut 200 OK
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        // vérification de la structure de pagination dans la réponse
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $json); // les livres sont dans "data"
        $this->assertArrayHasKey('page', $json);
    }

    // test 2 : vérification de la sécurité (création interdite aux utilisateurs non connectés)
    public function testCreateBookUnauthorized(): void
    {
        $client = static::createClient();

        // tentative de création sur l'URL v1
        $client->request('POST', '/api/v1/books', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Livre Test',
            'author' => 'Auteur Test',
            'isbn' => '0000000000',
            'publishedAt' => '2023-01-01'
        ]));

        // on s'attend à ce que le code ne soit PAS 201 (Created)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertNotSame(201, $statusCode);
    }
}