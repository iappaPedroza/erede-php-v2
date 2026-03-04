<?php

namespace ItsTecnologiaErede;

interface RedeUnserializable
{
    /**
     * @param string $serialized
     *
     * @return $this
     */
    public function jsonUnserialize(string $serialized): static;
}
