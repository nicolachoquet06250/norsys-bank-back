<?php

namespace NorsysBank\repositories;

use NorsysBank\models\User as ModelsUser;

class User {
    public static function findOne(float $id): ?ModelsUser
    {
        $db = file_get_contents(__DIR__.'/../../db/users.json');

        if ($db) {
            $db = json_decode($db, true);

            foreach ($db as $key => $modelArray) {
                $db[$key] = new ModelsUser(
                    id: $modelArray['id'], 
                    name: $modelArray['name'], 
                    displayName: $modelArray['displayName']
                );
            }

            return array_filter($db, fn(ModelsUser $user) => $user->getId() === $id)[0] ?? null;
        }

        return null;
    }

    public static function findFromEmail(string $email): ?ModelsUser
    {
        $db = file_get_contents(__DIR__.'/../../db/users.json');

        if ($db) {
            $db = json_decode($db, true);

            foreach ($db as $key => $modelArray) {
                $db[$key] = new ModelsUser(
                    id: $modelArray['id'], 
                    name: $modelArray['name'], 
                    displayName: $modelArray['displayName']
                );
            }

            return array_filter($db, fn(ModelsUser $user) => $user->getEmail() === $email)[0] ?? null;
        }

        return null;
    }

    public function add(ModelsUser $user): self
    {
        $db = file_get_contents(__DIR__.'/../../db/users.json');

        if ($db) {
            $db = json_decode($db, true);

            $db[] = [
                'identifiant' => $db[count($db) - 1]['identifiant'] + 1,
                'id' => $user->getId(),
                'name' => $user->getEmail(),
                'displayName' => $user->getName()
            ];

            file_put_contents(__DIR__.'/../../db/users.json', json_encode($db));
        }

        return $this;
    }
}