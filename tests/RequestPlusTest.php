<?php

class RequestPlusTest extends PHPUnit_Framework_TestCase
{
    private $bucket;
    private $bucketName;

    private function bucket()
    {
        if (!$this->bucket) {
            $host = getenv('HOST');
            $bucket = getenv('BUCKET');
            $cluster = new CouchbaseCluster($host);
            $this->bucket = $cluster->openBucket($bucket);

            $this->bucketName = $bucket;
        }

        return $this->bucket;
    }

    private function flush()
    {
        $this->bucket()->manager()->flush();
    }

    private function dropPrimaryIndex()
    {
        $query = CouchbaseN1qlQuery::fromString("drop primary index on {$this->bucketName}");
        $this->bucket()->query($query);
    }

    private function createPrimaryIndex()
    {
        $query = CouchbaseN1qlQuery::fromString("create primary index on {$this->bucketName} using GSI");
        $this->bucket()->query($query);
    }

    public function setUp()
    {
        parent::setUp();

        # Flush the bucket
        $this->flush();

        # Drop the primary index
        $this->dropPrimaryIndex();

        # Re-create the primary index
        $this->createPrimaryIndex();

        # Create a document
        $this->bucket()->insert('test', [
            'content' => 'test content'
        ]);
    }

    public function test()
    {
        $query = CouchbaseN1qlQuery::fromString("select * from {$this->bucketName} where content='test content'");
        $query->consistency(CouchbaseN1qlQuery::REQUEST_PLUS);
        $result = $this->bucket()->query($query);
        $this->assertEquals(1, count($result));
    }
}