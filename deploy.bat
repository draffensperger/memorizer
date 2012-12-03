cd ..
rm -f memorizer_deploy/*.*
rm -rf memorizer_deploy/
mkdir memorizer_deploy
cp Memorizer/*.php memorizer_deploy/
cd memorizer_deploy
af update memorizer
cd ..
cd Memorizer