<?php

namespace App\Tests\Api\Periods;

use App\Entity\Period;
use App\Tests\Api\ECampApiTestCase;

/**
 * @internal
 */
class ReadPeriodTest extends ECampApiTestCase {
    public function testGetSinglePeriodIsDeniedForAnonymousUser() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createBasicClient()->request('GET', '/periods/'.$period->getId());
        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testGetSinglePeriodIsDeniedForUnrelatedUser() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createClientWithCredentials(['email' => static::$fixtures['user4unrelated']->getEmail()])
            ->request('GET', '/periods/'.$period->getId())
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testGetSinglePeriodIsDeniedForInactiveCollaborator() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createClientWithCredentials(['email' => static::$fixtures['user5inactive']->getEmail()])
            ->request('GET', '/periods/'.$period->getId())
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testGetSinglePeriodIsAllowedForGuest() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createClientWithCredentials(['email' => static::$fixtures['user3guest']->getEmail()])
            ->request('GET', '/periods/'.$period->getId())
        ;

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $period->getId(),
            'description' => $period->description,
            'start' => $period->start->format('Y-m-d'),
            'end' => $period->end->format('Y-m-d'),
            '_links' => [
                'camp' => ['href' => $this->getIriFor('camp1')],
                'materialItems' => ['href' => '/material_items?period=%2Fperiods%2F'.$period->getId()],
                'days' => ['href' => '/periods/'.$period->getId().'/days'],
                'scheduleEntries' => ['href' => '/periods/'.$period->getId().'/schedule_entries'],
                'contentNodes' => ['href' => '/content_nodes?period=%2Fperiods%2F'.$period->getId()],
                'dayResponsibles' => ['href' => '/day_responsibles?day.period=%2Fperiods%2F'.$period->getId()],
            ],
        ]);
    }

    public function testGetSinglePeriodIsAllowedForMember() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createClientWithCredentials(['email' => static::$fixtures['user2member']->getEmail()])
            ->request('GET', '/periods/'.$period->getId())
        ;

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $period->getId(),
            'description' => $period->description,
            'start' => $period->start->format('Y-m-d'),
            'end' => $period->end->format('Y-m-d'),
            '_links' => [
                'camp' => ['href' => $this->getIriFor('camp1')],
                'materialItems' => ['href' => '/material_items?period=%2Fperiods%2F'.$period->getId()],
                'days' => ['href' => '/periods/'.$period->getId().'/days'],
                'scheduleEntries' => ['href' => '/periods/'.$period->getId().'/schedule_entries'],
            ],
        ]);
    }

    public function testGetSinglePeriodIsAllowedForManager() {
        /** @var Period $period */
        $period = static::getFixture('period1');
        static::createClientWithCredentials()->request('GET', '/periods/'.$period->getId());
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $period->getId(),
            'description' => $period->description,
            'start' => $period->start->format('Y-m-d'),
            'end' => $period->end->format('Y-m-d'),
            '_links' => [
                'camp' => ['href' => $this->getIriFor('camp1')],
                'materialItems' => ['href' => '/material_items?period=%2Fperiods%2F'.$period->getId()],
                'days' => ['href' => '/periods/'.$period->getId().'/days'],
                'scheduleEntries' => ['href' => '/periods/'.$period->getId().'/schedule_entries'],
            ],
        ]);
    }

    public function testGetSinglePeriodFromCampPrototypeIsAllowedForUnrelatedUser() {
        /** @var Period $period */
        $period = static::getFixture('period1campPrototype');

        // Precondition: no collaborations on the camp.
        // This is to make sure a left join from camp to collaborations is used.
        $this->assertEmpty($period->camp->collaborations);

        static::createClientWithCredentials()->request('GET', '/periods/'.$period->getId());
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $period->getId(),
            'description' => $period->description,
            'start' => $period->start->format('Y-m-d'),
            'end' => $period->end->format('Y-m-d'),
            '_links' => [
                'camp' => ['href' => $this->getIriFor('campPrototype')],
            ],
        ]);
    }
}
