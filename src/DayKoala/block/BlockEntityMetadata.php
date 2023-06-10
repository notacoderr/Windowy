<?php

/*
 *  __          ___           _                     
 *  \ \        / (_)         | |                    
 *   \ \  /\  / / _ _ __   __| | _____      ___   _ 
 *    \ \/  \/ / | | '_ \ / _` |/ _ \ \ /\ / / | | |
 *     \  /\  /  | | | | | (_| | (_) \ V  V /| |_| |
 *      \/  \/   |_|_| |_|\__,_|\___/ \_/\_/  \__, |
 *                                             __/ |
 *                                            |___/ 
 *  @author DayKoala
 *  @link https://github.com/DayKoala/Windowy
 *  @social https://twitter.com/DayKoala
 * 
 */

namespace codeeeh\korr\libs\koala\block;

use pocketmine\block\tile\TileFactory;
use pocketmine\block\tile\Tile;

use pocketmine\block\Block;

use pocketmine\math\Vector3;

use pocketmine\world\Position;

use pocketmine\nbt\tag\CompoundTag;

use pocketmine\network\mcpe\convert\TypeConverter;

use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;

use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class BlockEntityMetadata{

    protected $tile, $block;

    public function __construct(string $tile, Block $block) {
        $this->block = $block;
        $this->tile = TileFactory::getInstance()->getSaveId($tile);
    }

    public function create(Vector3 $pos, ?Vector3 $pair = null, ?String $name = null) : Array{
        $packets = [$this->writeBlock($pos, $this->block), $this->writeActor($pos, $pair, $name)];
        if($pair instanceof Vector3){
           $packets[] = $this->writeBlock($pair, $this->block);
           $packets[] = $this->writeActor($pair, $pos, $name);
        }
        return $packets;
    }

    public function remove(Position $pos, ?Vector3 $pair = null) : Array{
        $packets = [$this->writeBlock($pos, $pos->getWorld()->getBlock($pos))];
        if($pair instanceof Vector3){
           $packets[] = $this->writeBlock($pair, $pos->getWorld()->getBlock($pair));
        }
        return $packets;
    }

    protected function writeNBT(Vector3 $pos, ?Vector3 $pair = null, ?String $name = null) : CompoundTag{
        $nbt = CompoundTag::create()

        ->setString(Tile::TAG_ID, $this->tile)

        ->setInt(Tile::TAG_X, $pos->x)
        ->setInt(Tile::TAG_Y, $pos->y)
        ->setInt(Tile::TAG_Z, $pos->z);

        if(is_string($name)) $nbt->setString("CustomName", $name);

        if($pair instanceof Vector3){
           $nbt

           ->setInt("pairx", $pair->x)
           ->setInt("pairz", $pair->z);
        }
        return $nbt;
    }

    protected function writeBlock(Vector3 $pos, Block $block) : UpdateBlockPacket {
		$translator = TypeConverter::getInstance()->getBlockTranslator();
		
        return UpdateBlockPacket::create(
			BlockPosition::fromVector3($pos),
			$translator->internalIdToNetworkId($block->getStateId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL
		);
    }

    protected function writeActor(Vector3 $pos, ?Vector3 $pair = null, ?String $name = null) : BlockActorDataPacket{
        return BlockActorDataPacket::create(BlockPosition::fromVector3($pos), new CacheableNbt($this->writeNBT($pos, $pair, $name)));
    }

}
