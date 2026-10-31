<?php

function getAvatarOptions(): array
{
  return [
    "01_male_black_hoodie.png",
    "02_female_braids_yellow_hoodie.png",
    "03_male_blue_hoodie.png",
    "04_female_glasses_pink_top.png",
    "05_male_glasses_green_shirt.png",
    "06_female_afro_purple_top.png",
    "07_male_headphones_black_top.png",
    "08_female_beige_hijab.png",
    "09_female_curly_red_hoodie.png",
    "10_male_cap_blue_jacket.png",
    "11_female_bun_purple_top.png",
    "12_male_yellow_hoodie.png",
    "13_male_sunglasses_black_jacket.png",
    "14_female_cap_white_top.png",
    "15_male_suit_red_tie.png",
    "16_female_green_top.png"
  ];
}

function getAvatarFilename($avatar): string
{
  $options = getAvatarOptions();

  return is_string($avatar) && in_array($avatar, $options, true)
    ? $avatar
    : $options[0];
}

function getAvatarGender($avatar): ?string
{
  if (!is_string($avatar)) {
    return null;
  }

  if (str_contains($avatar, "_female_")) {
    return "female";
  }

  if (str_contains($avatar, "_male_")) {
    return "male";
  }

  return null;
}
