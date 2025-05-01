<?php

namespace App\Api\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseFormatter
{
    private SerializerInterface $serializer;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        SerializerInterface $serializer,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->serializer = $serializer;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Format the data according to request parameters
     */
    public function formatResponse(Request $request, $data, array $context = []): mixed
    {
        // Don't process null data or scalars
        if ($data === null || is_scalar($data)) {
            return $data;
        }

        // Step 1: Serialize to array (applying serialization groups)
        $serialized = $this->serializer->serialize($data, 'json', $context);
        $normalized = json_decode($serialized, true);

        // If not array after normalization, just return it
        if (!is_array($normalized)) {
            return $normalized;
        }

        // Step 2: Apply fields filtering if specified
        if ($request->query->has('fields')) {
            $normalized = $this->filterFields($normalized, $request->query->get('fields'));
        }

        // Step 3: Sort results if specified
        if ($request->query->has('sort')) {
            $normalized = $this->sortResults($normalized, $request->query->get('sort'));
        }

        return $normalized;
    }

    /**
     * Filter fields based on comma-separated list
     * Example: ?fields=id,name,email
     */
    private function filterFields(array $data, string $fieldsParam): array
    {
        $fields = array_map('trim', explode(',', $fieldsParam));

        // Handle single objects
        if (isset($data['id']) || !isset($data[0])) {
            return array_intersect_key($data, array_flip(array_filter($fields)));
        }

        // Handle collections
        return array_map(function ($item) use ($fields) {
            return array_intersect_key($item, array_flip(array_filter($fields)));
        }, $data);
    }

    /**
     * Sort results based on field and direction
     * Example: ?sort=name,-created_at (ascending by name, descending by created_at)
     */
    private function sortResults(array $data, string $sortParam): array
    {
        // If it's not a collection, return as is
        if (!isset($data[0]) || !is_array($data[0])) {
            return $data;
        }

        $sortFields = array_map('trim', explode(',', $sortParam));

        usort($data, function ($a, $b) use ($sortFields) {
            foreach ($sortFields as $field) {
                $descending = false;

                // Check for descending sort
                if (str_starts_with($field, '-')) {
                    $descending = true;
                    $field = substr($field, 1);
                }

                // Skip if field doesn't exist in either item
                if (!isset($a[$field]) || !isset($b[$field])) {
                    continue;
                }

                // Compare values
                $valueA = $a[$field];
                $valueB = $b[$field];

                // Handle case insensitive string comparison
                if (is_string($valueA) && is_string($valueB)) {
                    $cmp = strcasecmp($valueA, $valueB);
                } else {
                    $cmp = $valueA <=> $valueB;
                }

                // If values are different, return comparison result
                if ($cmp !== 0) {
                    return $descending ? -$cmp : $cmp;
                }
            }

            // All values are equal
            return 0;
        });

        return $data;
    }
}
