<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\View\JsonView;
use OutOfBoundsException;

/**
 * Cards Controller
 *
 */
class CardsController extends AppController
{
    private const NUMBER_PER_SHAPE = 13;
    private const SHAPES = ['S', 'H', 'D', 'C'];
    private const NUMBERS = [
        0 => 'A',
        9 => 'X',
        10 => 'J',
        11 => 'Q',
        12 => 'K',
    ];

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    public function getCards(string $id)
    {
        $numberOfPlayers = $id;
        if (!is_numeric($numberOfPlayers)) {
            $this->set('status', 400);
            $this->set('message', 'Invalid number format');
            $this->viewBuilder()->setOption('serialize', ['status' ,'message']);
            return;
        }

        $numberOfPlayers = intval($numberOfPlayers);
        if ($numberOfPlayers <= 0 || $numberOfPlayers >= 100) {
            $this->set('status', 400);
            $this->set('message', 'Number out of range');
            $this->viewBuilder()->setOption('serialize', ['status' ,'message']);
            return;
        }

        try {
            $cards = $this->distributeCards($numberOfPlayers);

        } catch (\OutOfBoundsException $ex) {
            $this->set('status', 500);
            $this->set('message', $ex->getMessage());
            $this->viewBuilder()->setOption('serialize', ['status' ,'message']);
            return;
        }

        $this->set('status', 200);
        $this->set('cards', $cards);
        $this->viewBuilder()->setOption('serialize', ['status', 'cards']);
    }

    /**
     * Below are the methods for preparing the cards to players
     * Could have used something like CardsService (Laravel)
     * and let the Controller handle the request/response
     */

    /**
     * Distribute cards to each players
     */
    private function distributeCards(int $numberOfPlayers): array
    {
        $cards = $this->prepareCards();
        $distributedCards = [];

        for ($i=0; $i < count($cards); $i++) {
            $distributedCards[$i % $numberOfPlayers][] = $this->convertIdToCard($cards[$i]);
        }

        if ($numberOfPlayers > count($cards)) {
            $distributedCards = array_merge($distributedCards, array_fill(0, ($numberOfPlayers - count($cards)), null));
        }

        return $distributedCards;
    }

    /**
     * Prepare cards
     */
    private function prepareCards(): array
    {
        $cards = range(0, 51);
        shuffle($cards);
        return $cards;
    }

    /**
     * Convert index to card
     */
    public function convertIdToCard(int $id): string
    {
        $number = $id % self::NUMBER_PER_SHAPE;
        $shape = ($id >= self::NUMBER_PER_SHAPE) ? (int)($id / self::NUMBER_PER_SHAPE) : 0;

        return $this->mapShape($shape) . '-' . $this->mapNumber($number);
    }

    /**
     * Map Shape to Character
     *
     * 0 = S
     * 1 = H
     * 2 = D
     * 3 = C
     */
    private function mapShape(int $shape): string
    {
        if (!in_array($shape, array_keys(self::SHAPES))) {
            throw new \OutOfBoundsException('Shape index out of bound!');
        }

        return self::SHAPES[$shape];
    }

    private function mapNumber(int $number): string
    {
        if (!in_array($number, array_keys(self::NUMBERS))) {
            if ($number < 1 || $number > 12) {
                throw new \OutOfBoundsException('Number index out of bound!');
            }
            return (string)$number;
        }

        return self::NUMBERS[$number];
    }
}
