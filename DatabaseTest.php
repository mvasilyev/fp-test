<?php

namespace FpDbTest;

use Exception;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');

        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );

        $results[] = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            $results[] = $this->db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
            );
        }

        try {
            $this->db->buildQuery(
                'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
                [['name', 'email'], 'not a number', true]
            );
        } catch (\Exception $exception) {
            $results[] = $exception->getMessage();
        }

        try {
            $this->db->buildQuery(
                'UPDATE users SET ?a WHERE user_id = -1',
                [7]
            );
        } catch (\Exception $exception) {
            $results[] = $exception->getMessage();
        }

        $results[] = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
            ['user_id', ['a', 'b', 'c'], 1]);

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
            'Not a number|bool',
            'Not an array',
            'SELECT name FROM users WHERE `user_id` IN (\'a\', \'b\', \'c\') AND block = 1',
        ];

        if ($results !== $correct) {
            throw new Exception('Failure.');
        }
    }
}
