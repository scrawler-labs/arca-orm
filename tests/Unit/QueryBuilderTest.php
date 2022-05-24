<?php

it("checks if db()->find()->first() returns first record", function () {
    $user = db()->find('user')->first();
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = 1");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
});
