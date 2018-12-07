#include "fonction.h"


int main(int argc, char *argv[])
{
//interface graphique
///////////////////////////////////////////////////////////////////////
QApplication app(argc, argv);
Fenetre fenetre;
fenetre.setWindowTitle("TRAITEMENT_OCR");
fenetre.show();
return app.exec();

}
