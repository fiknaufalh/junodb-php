<?php

namespace JunoPhpClient\IO\Protocol;

class MetaMessageFixedField extends MetaMessageTagAndType
{
  private int $content;
  private ?string $variableContent = null;
  public function __construct(int $tagAndSizeType)
  {
    parent::__construct($tagAndSizeType);
  }

  public function getContent(): int
  {
    return $this->content;
  }

  public function setContent(int $content): void
  {
    $this->content = $content;
  }

  public function getVariableContent(): ?string
  {
    return $this->variableContent;
  }

  public function setVariableContent(string $variableContent): void
  {
    $this->variableContent = $variableContent;
  }

  public function readBuf(string $in): self
  {
    $size = $this->getFieldSize();
    if ($size === 4) {
      $this->content = unpack('N', $in)[1];
    } else {
      $this->variableContent = substr($in, 0, $size);
    }
    return $this;
  }

  public function writeBuf(string &$out): void
  {
    $size = $this->getFieldSize();
    if ($size === 4) {
      $value = $this->content & 0xFFFFFFFF;
      $out .= pack('N', $value);
    } else {
      $out .= $this->variableContent;
    }
  }

  public function getLength(): int
  {
    if ($this->variableContent !== null) {
      return strlen($this->variableContent);
    } else {
      return 4;
    }
  }
}
