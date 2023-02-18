<?php

/*
1. In the getUsers and user methods, the values of the parameters are not properly escaped, which can lead to SQL injection attacks.

2. In the getUsers method, the from field is a reserved keyword in SQL and should be enclosed in backticks.

3. In the user method, the query returns only one row, so fetch method should be used instead of fetchAll.

4. The class name used in the getUsers method (Manager\User) is not the same as the class name used in the namespace (Gateway\User).

5. The add method does not properly bind the lastName and age parameters in the prepared statement.
*/
namespace Gateway;

use PDO;

class User
{
    private static ?PDO $instance = null;

    /**
     * Implementation of the singleton pattern
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            $dsn = 'mysql:dbname=db;host=127.0.0.1';
            $user = 'dbuser';
            $password = 'dbpass';
            self::$instance = new PDO($dsn, $user, $password);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exceptions
        }

        return self::$instance;
    }

    /**
     * Returns a list of users older than the given age.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, `from`, age, settings FROM Users WHERE age > :ageFrom LIMIT :limit");
        $stmt->bindParam(':ageFrom', $ageFrom, PDO::PARAM_INT); // Bind parameters to prevent SQL injection
        $stmt->bindValue(':limit', Manager\User::LIMIT, PDO::PARAM_INT); // Use the constant from the Manager\User class
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings'], true); // Decode JSON as an array
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['lastName'],
                'from' => $row['from'],
                'age' => $row['age'],
                'key' => $settings['key'] ?? null, // Check if the key exists
            ];
        }

        return $users;
    }

    /**
     * Returns a user by their name.
     * @param string $name
     * @return array|null
     */
    public static function getUserByName(string $name): ?array // Change method name to getUserByName and return null if the user is not found
    {
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, `from`, age, settings FROM Users WHERE name = :name");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR); // Bind parameters to prevent SQL injection
        $stmt->execute();
        $userByName = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userByName) {
            return null;
        }

        $settings = json_decode($userByName['settings'], true); // Decode JSON as an array

        return [
            'id' => $userByName['id'],
            'name' => $userByName['name'],
            'lastName' => $userByName['lastName'],
            'from' => $userByName['from'],
            'age' => $userByName['age'],
            'key' => $settings['key'] ?? null, // Check if the key exists
        ];
    }


    /**
	 * Добавляет пользователя в базу данных.
	 * @param string $name
	 * @param string $lastName
	 * @param int $age
	 * @return int|string - returns the ID of the last inserted row or an error message if there's an exception
	 */
	public static function add(string $name, string $lastName, int $age): int|string
	{
		/*
		 * Added a try-catch block to handle any exceptions that may occur when executing the query.
		 * Changed the execute() method to use named parameters to prevent SQL injection attacks and avoid errors caused by special characters in names or last names.
		 * Added bindParam() to bind the named parameters to their corresponding values and data types.
		 * Changed the return type to int|string to indicate that the method can return either the ID of the last inserted row or an error message if there's an exception.
		*/
	    try {
	        $stmt = self::getInstance()->prepare("INSERT INTO Users (name, lastName, age) VALUES (:name, :lastName, :age)");
	        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
	        $stmt->bindParam(':lastName', $lastName, PDO::PARAM_STR);
	        $stmt->bindParam(':age', $age, PDO::PARAM_INT);
	        $stmt->execute();
	        
	        return self::getInstance()->lastInsertId();
	    } catch (PDOException $e) {
	        // Handle the exception gracefully, log it or rethrow it
	        return "Error: " . $e->getMessage();
	    }
	}
}
