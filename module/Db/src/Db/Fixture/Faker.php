<?php

namespace Db\Fixture;

use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\Query\Expr;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;

/**
 * Generate random data for all model classes
 */
class Faker implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $loop = new \Db\Entity\Loop;
        $manager->persist($loop);
        $manager->flush();
        $loop->setLoop($loop);
        $manager->flush();

        $loop = new \Db\Entity\Loop;
        $manager->persist($loop);
        $manager->flush();
        $loop->setLoop($loop);
        $manager->flush();

        $generator = Factory::create();
        $populator = new Populator($generator, $manager);

        $populator->addEntity('Db\Entity\Artist', 100);
        $populator->addEntity('Db\Entity\Album', 1000);

        $populator->execute();

    }
}

