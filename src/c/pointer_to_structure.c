/* Unit 8: Structure and Union
 * Pointer to structure, and the -> (arrow) operator. */
#include <stdio.h>

struct Student {
    char name[50];
    int roll;
};

int main(void) {
    struct Student s1 = {"Hari", 5};
    struct Student *ptr = &s1;

    printf("Via dot operator   : %s, roll %d\n", s1.name, s1.roll);
    printf("Via arrow operator : %s, roll %d\n", ptr->name, ptr->roll);

    return 0;
}
