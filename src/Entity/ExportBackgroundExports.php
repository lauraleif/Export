<?php

namespace Export\Entity;

use Omeka\Entity\AbstractEntity;
use DateTime;

/**
 * @Entity
 */
class ExportBackgroundExports extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="string", unique=true)
     * @JoinColumn(nullable=false)
     */
    protected $filename;

    /**
     * @Column(type="string")
     * @JoinColumn(nullable=false)
     */
    protected $extension;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $query;

    /**
     * @Column(type="string")
     * @JoinColumn(nullable=false)
     */
    protected $resourceType;

    /**
     * @Column(type="integer")
     * @JoinColumn(nullable=false)
     */
    protected $resourcesCount;

    /**
     * @Column(type="string")
     * @JoinColumn(nullable=false)
     */
    protected $fileUri;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    public function getId()
    {
        return $this->id;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(DateTime $created)
    {
        $this->created = $created;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    public function getResourcesCount()
    {
        return $this->resourcesCount;
    }

    public function setResourcesCount($resourcesCount)
    {
        $this->resourcesCount = $resourcesCount;

        return $this;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    public function getFileUri()
    {
        return $this->fileUri;
    }

    public function setFileUri($fileUri)
    {
        $this->fileUri = $fileUri;

        return $this;
    }
}
