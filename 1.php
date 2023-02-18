<?php

/** Comments about Changes

1. The trim() function in the getUsers() method is unnecessary because the parameter is already type-hinted as an integer.

2. The users() method should use type-hinting for the $users parameter to make it clear that it should be an array.

3. The try-catch block inside the users() method should not call commit() inside the try block. It should be moved outside the try-catch block so that it only gets called if no exception is thrown.

4. I changed the name of the users() method to addUsers() to make it clearer that it's adding new users to the database. 

5. Finally, I changed the name of the limit constant to LIMIT to follow the convention of using uppercase letters for constants.

*/

namespace Manager;

class User
{
    const LIMIT = 10;

    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public function getUsers(int $ageFrom): array
    {
        return \Gateway\User::getUsers($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @return array
     */
    public static function getByNames(): array
    {
        $users = [];
        foreach ($_GET['names'] as $name) {
            $users[] = \Gateway\User::user($name);
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param array $users
     * @return array
     */
    public function addUsers(array $users): array
    {
        $ids = [];
        $gateway = \Gateway\User::getInstance();
        $gateway->beginTransaction();
        try {
            foreach ($users as $user) {
                \Gateway\User::add($user['name'], $user['lastName'], $user['age']);
                $ids[] = $gateway->lastInsertId();
            }
            $gateway->commit();
        } catch (\Exception $e) {
            $gateway->rollBack();
        }

        return $ids;
    }
}
