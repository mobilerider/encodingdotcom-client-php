<?php

namespace Encoding\Media;

class Parser
{
    protected function prepareData($data)
    {
        if (!$data || is_array($data)) {
            return $data;
        }

        if ($data instanceof \SimpleXmlElement) {
            $data = new \SimpleXMLIterator($data->asXML());
        }

        if ($data instanceof \SimpleXMLIterator) {
            $data = sxi_to_array($data);
        } else if ($data instanceof \stdClass) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            throw new \Exception('Invalid data type');
        }

        return $data;
    }

    public static function parseMediaStatus($data)
    {
        $cleanData = [];

        $cleanData['id'] = $data['id'];
        unset($data['id']);

        $cleanData['userId'] = $data['userid'];
        unset($data['userid']);

        $sources = $data['sourcefile'];

        if (!is_array($sources)) {
            $sources = [$sources];
        }

        $cleanData['sources'] = $sources;
        unset($data['sourcefile']);

        $cleanData['formats'] = [];

        if (isset($data['format'])) {
            $formats = $data['format'];
            // Check if single format
            if (!is_numeric(key($formats))) {
                $formats = [$formats];
            }

            foreach($formats as $format) {
                $cleanFormat = [];

                $cleanFormat['id'] = $format['id'];
                unset($format['id']);

                $cleanFormat['output'] = $format['output'];
                unset($format['output']);

                $destinationsStatus = $format['destination_status'];
                $destinationsStatus = is_array($destinationsStatus) ? $destinationsStatus : [$destinationsStatus];
                unset($format['destination_status']);

                $destinations = $format['destination'];
                $destinations = is_array($destinations) ? $destinations : [$destinations];
                unset($format['destination']);

                foreach ($destinations as $index => $destination) {
                    $cleanFormat['destinations'][$destination] = $destinationsStatus[$index];
                }

                // Remove empty data
                $format = array_filter($format, function($x) {
                    return !empty($x);
                });

                // Resolve properties
                $properties = array_flip(['status', 'created', 'started', 'finished', 'duration', 'converttime', 'convertedsize', 'queued']);
                $cleanFormat['properties'] = array_intersect_key($format, $properties);

                // Assumes rest of the data as options
                $cleanFormat['options'] = array_diff_key($format, $properties);

                $cleanData['formats'][] = $cleanFormat;
            }
        }

        unset($data['format']);

        $data = array_filter($data, function($x) {
            return !empty($x);
        });

        $cleanData['properties'] = $data;

        return $cleanData;
    }

    public static function parseMediaInfo($rawData)
    {
        $data = [
            'sources' => []
        ];

        // Check if multiple sources exist
        if (isset($rawData['source'])) {
            $sources = $rawData['source'];
        } else {
            $sources = [$rawData];
        }

        foreach ($sources as &$sourceData) {
            $sourceData = array_filter($sourceData, function($x) {
                return !empty($x);
            });

            // Extract values
            $source = [
                'streams' => []
            ];

            $embeddedTrackTypes = ['video', 'audio'];
            $id = 1; // Assume 1 for video if exists and rest for audio

            // Check for embedded track properties in root file
            foreach ($embeddedTrackTypes as $index => $type) {
                $trackData = [];
                $prefix = $type . '_';

                foreach ($sourceData as $key => $value) {
                    if (strpos($key, $prefix) === 0) {
                        $trackData[str_replace($prefix, '', $key)] = $value;
                        unset($sourceData[$key]);
                    }
                }

                if ($trackData) {
                    $source['streams'][$type][$id] = $trackData;
                    $id++;
                }
            }

            // Check for embedded stream data
            $embeddedStreamTypes = ['text'];

            foreach ($embeddedStreamTypes as $type) {
                $streamKey = $type . '_stream';

                if (array_key_exists($streamKey, $sourceData)) {
                    $source['streams'][$type] = $rawData[$streamKey];
                    unset($sourceData[$streamKey]);
                }
            }

            $source['properties'] = $sourceData;
            $data['sources'][] = $source;
        }

        return $data;
    }
}
