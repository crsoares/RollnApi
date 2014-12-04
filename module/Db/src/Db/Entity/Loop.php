<?php

namespace Db\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Loop
 */
class Loop
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $loopBack;

    /**
     * @var \Db\Entity\Loop
     */
    private $loop;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loopBack = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add loopBack
     *
     * @param \Db\Entity\Loop $loopBack
     * @return Loop
     */
    public function addLoopBack(\Db\Entity\Loop $loopBack)
    {
        $this->loopBack[] = $loopBack;

        return $this;
    }

    /**
     * Remove loopBack
     *
     * @param \Db\Entity\Loop $loopBack
     */
    public function removeLoopBack(\Db\Entity\Loop $loopBack)
    {
        $this->loopBack->removeElement($loopBack);
    }

    /**
     * Get loopBack
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLoopBack()
    {
        return $this->loopBack;
    }

    /**
     * Set loop
     *
     * @param \Db\Entity\Loop $loop
     * @return Loop
     */
    public function setLoop(\Db\Entity\Loop $loop = null)
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * Get loop
     *
     * @return \Db\Entity\Loop 
     */
    public function getLoop()
    {
        return $this->loop;
    }
}
