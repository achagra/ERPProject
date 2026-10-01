<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\Fournisseur;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductCanBeLinkedToClientAndFournisseur(): void
    {
        $product = new Product();
        $client = new Client();
        $fournisseur = new Fournisseur();

        $product->setClient($client);
        $product->setFournisseur($fournisseur);

        $this->assertSame($client, $product->getClient());
        $this->assertSame($fournisseur, $product->getFournisseur());
    }
}
