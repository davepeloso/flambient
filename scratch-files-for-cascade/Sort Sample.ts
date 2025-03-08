
 public function groupImageExposures(Collection $imageExposures): array
        {
            $groupedExposures = [];
            $groupNumber = 1;
            $firstExposureProgram = null;
            $previousExposureProgram = null;

            foreach ($imageExposures->sortBy('sequence_column') as $imageExposure) {
                $currentExposureProgram = $imageExposure->exposure_program;

                if ($firstExposureProgram === null) {
                    $firstExposureProgram = $currentExposureProgram;
                } elseif ($currentExposureProgram === $firstExposureProgram && $currentExposureProgram !== $previousExposureProgram) {
                    $groupNumber++;
                }

                $groupName = "Flash / Ambient $groupNumber";
                if (!isset($groupedExposures[$groupName])) {
                    $groupedExposures[$groupName] = [
                        'mode_one'  => [],
                        'mode_two'  => [],
                        'exposures' => [],
                    ];
                }

                // Add the image name to the appropriate mode array
                if ($currentExposureProgram === $firstExposureProgram) {
                    $groupedExposures[$groupName]['mode_one'][] = $imageExposure->filename;
                } else {
                    $groupedExposures[$groupName]['mode_two'][] = $imageExposure->filename;
                }

                // Keep the original exposure object in the 'exposures' array
                $groupedExposures[$groupName]['exposures'][] = $imageExposure;

                $previousExposureProgram = $currentExposureProgram;
            }

            return $groupedExposures;
        }
