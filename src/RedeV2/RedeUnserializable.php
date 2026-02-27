<?php

namespace RedeV2;

interface RedeUnserializable
{
    /**
     * @param string $serialized
     *
     * @return $this
     */
    public function jsonUnserialize(string $serialized): static;
}
